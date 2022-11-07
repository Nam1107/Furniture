<?php

class wishListModel extends Controllers
{
    function getList($userID, $page, $perPage)
    {
        $offset = $perPage * ($page - 1);
        $total = custom(
            "SELECT COUNT(ID) as total
            FROM (
                SELECT wishList.ID
                FROM `wishList`,product
                WHERE wishList.userID = $userID
                AND `wishList`.productID = product.ID
                AND product.IsPublic = 1
            ) AS B
        "
        );

        $check = ceil($total[0]['total'] / $perPage);

        $wishList = custom("
        SELECT A.* , category.name AS category
        FROM (SELECT *, IF(startSale<NOW() && endSale>NOW(), '1', '0') AS statusSale
        FROM product WHERE IsPublic = 1) AS A,category,wishList
        WHERE A.categoryID = category.ID
        AND A.ID = wishList.productID
        
        AND wishList.userID = $userID
        LIMIT $perPage OFFSET $offset
        ");
        $res = $this->loadList($total[0]['total'], $check, $page, $wishList);
        return ($res);
    }

    function getDetail($userID, $productID)
    {
        $condition = [
            "userID" => $userID,
            "productID" =>  $productID
        ];
        $res = selectOne('wishList', $condition);
        return $res;
    }
    function delete($userID, $productID)
    {
        $condition = [
            "userID" => $userID,
            "productID" => $productID
        ];
        delete('wishList', $condition);
    }
    function create($userID, $productID)
    {
    }
}