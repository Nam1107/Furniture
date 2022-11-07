<?php

class wishList extends Controllers
{
    public $validate_user;
    public $middle_ware;
    public $wishlist_model;
    public function __construct()
    {
        $this->wishlist_model = $this->model('wishListModel');
        $this->middle_ware = new middleware();
        set_error_handler(function ($err_severity, $err_msg, $err_file, $err_line, array $err_context) {
            throw new ErrorException($err_msg, 0, $err_severity, $err_file, $err_line);
        }, E_WARNING);
    }

    public function getWishList()
    {
        $this->middle_ware->checkRequest('GET');
        $this->middle_ware->userOnly();
        $userID = $_SESSION['user']['ID'];
        $json = file_get_contents("php://input");
        $sent_vars = json_decode($json, TRUE);
        try {
            $page = $sent_vars['page'];
            $perPage = $sent_vars['perPage'];
        } catch (Error $e) {
            $this->loadErrors(400, 'Error: input is invalid');
        }
        $res = $this->wishlist_model->getList($userID, $page, $perPage);

        dd($res);
        exit();
    }

    public function removeProduct($id = 0)
    {
        $this->middle_ware->checkRequest('DELETE');
        $this->middle_ware->userOnly();
        $userID = $_SESSION['user']['ID'];
        $check = $this->wishlist_model->getDetail($userID, $id);
        if (!$check) {
            $res['status'] = 0;
            $res['errors'] = 'Not found this product in your wishlist';
            dd($res);
            exit();
        }
        $this->wishlist_model->delete($userID, $id);
        $wishList = custom("
        SELECT A.* , category.name AS category
        FROM (SELECT *, IF(startSale<NOW() && endSale>NOW(), '1', '0') AS statusSale
        FROM product) AS A,category,wishList
        WHERE A.categoryID = category.ID
        AND A.ID = wishList.productID
        AND wishList.userID = $userID
        ");
        $res['status'] = 1;
        $res['obj'] = $wishList;
        dd($res);
        exit();
    }

    public function addProduct($id = 0)
    {
        $this->middle_ware->checkRequest('POST');
        $this->middle_ware->userOnly();
        $check = selectOne('product', ['ID' => $id]);
        if (!$check) {
            $res['status'] = 0;
            $res['errors'] = 'This product does not exist';
            dd($res);
            exit();
        }
        $userID = $_SESSION['user']['ID'];

        $condition = [
            "userID" => $userID,
            "productID" => $id
        ];
        $check = selectOne('wishList', $condition);
        if (!$check) {
            $condition['createdAt'] = currentTime();
            create('wishList', $condition);
            $wishList = custom("
            SELECT A.* , category.name AS category
            FROM (SELECT *, IF(startSale<NOW() && endSale>NOW(), '1', '0') AS statusSale
            FROM product) AS A,category,wishList
            WHERE A.categoryID = category.ID
            AND A.ID = wishList.productID
            AND wishList.userID = $userID
            ");
            $res['status'] = 1;
            $res['obj'] = $wishList;
            dd($res);
            exit();
        } else {
            $res['status'] = 0;
            $res['errors'] = 'This product has exists in your list';
            dd($res);
            exit();
        }
    }
}