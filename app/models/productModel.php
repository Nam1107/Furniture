<?php

class productModel extends Controllers
{
    protected $table = 'product';
    public $model_product;
    public $middle;
    public function __construct()
    {
        $this->middle = new middleware();
    }

    public function getDetail($id, $IsPublic)
    {
        $userID = 0;
        $obj = $this->middle->authenToken();
        if ($obj['status'] == 1) {
            $userID = $_SESSION['user']['id'];
        }

        $obj = custom("
            SELECT *
            FROM `product`
            WHERE product.id = $id
            AND is_public = 1
        
        ");
        if (empty($obj)) {
            return null;
        } else {
            $obj = $obj[0];
        }
        $categoryID = $obj['category_id'];
        unset($obj['category_id']);
        $check = custom("SELECT * FROM category WHERE id = $categoryID");
        if (empty($check)) {
            $obj['category'] = $check[0]['name'];
        }
        $gallery = selectAll('product_gallery', ['product_id' => $id]);

        $obj['gallery'] = $gallery;
        $obj['color'] = custom("SELECT color,image
        FROM product_variation
        WHERE product_id = $id
        GROUP BY color
        ");

        $size = custom("SELECT size
        FROM product_variation
        WHERE product_id = $id
        GROUP BY size
        ");

        $a = array();
        if ($size) {
            $a = array_column($size, 'size');;
        }
        $obj['size'] = $a;

        $obj['stock'] = custom("SELECT id,color,size,sub_price,sum(stock) AS stock
        FROM product_variation
        WHERE product_id = $id
        GROUP BY color,size
        ");

        $wish = custom("
        SELECT *
            FROM wish_list
            WHERE user_id = $userID
            AND product_id = $id
        ");

        if (!$wish) {
            $obj['wish_list'] = 0;
        } else $obj['wish_list'] = 1;




        return ($obj);
    }

    public function getList($page, $perPage, $name, $category, $IsPublic, $sale, $sortBy, $sortType)
    {
        $offset = $perPage * ($page - 1);

        $total = custom(
            "SELECT COUNT(ID) as total
            FROM (
                SELECT A.* , category.name AS category
                FROM (SELECT *, IF(startSale<NOW() && endSale>NOW(), '1', '0') AS statusSale
                FROM product) AS A,category
                WHERE A.categoryID = category.ID
                AND category.name LIKE '%$category%'
                AND A.name LIKE '%$name%'
                AND statusSale LIKE '%$sale%'
                AND IsPublic LIKE '%$IsPublic%'
            ) AS B
        "
        );
        $check = ceil($total[0]['total'] / $perPage);
        $obj = custom(
            "SELECT A.* , category.name AS category, IF(A.statusSale = '1', A.priceSale, A.price) AS curPrice
            FROM (SELECT *, IF(startSale<NOW() && endSale>NOW(), '1', '0') AS statusSale
            FROM product) AS A,category
            WHERE A.categoryID = category.ID
            AND category.name LIKE '%$category%'
            AND A.name LIKE '%$name%'
            AND statusSale LIKE '%$sale%'
            AND IsPublic LIKE '%1%'
            ORDER BY $sortBy $sortType
            LIMIT $perPage OFFSET $offset
            
            
            "
        );

        $res['totalCount'] = $total[0]['total'];
        $res['numOfPage'] = $check;
        $res['page'] = $page;
        $res['obj'] = $obj;
        return $res;
    }
}