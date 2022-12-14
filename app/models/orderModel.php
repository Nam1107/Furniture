<?php

class orderModel extends Controllers
{
    public function __construct()
    {
        set_error_handler(function ($severity, $message, $file, $line) {
            throw new ErrorException($message, 0, $severity, $file, $line);
        }, E_WARNING);
    }
    function getDetail($order_id, $value = '*', $all = 0)
    {

        $order = custom("
        SELECT $value
        FROM `tbl_order`
        WHERE `tbl_order`.id = $order_id
        ORDER BY id DESC
        ");

        $total = custom("
        SELECT SUM(`order_detail`.unit_price*`order_detail`.quantity) AS total,  SUM(`order_detail`.quantity) AS num_of_product
        FROM `order_detail`
        WHERE  order_detail.order_id= $order_id
        GROUP BY
        `order_detail`.order_id
        ");

        if (!$order) {
            return null;
        }

        $order = $order[0];

        if (!empty($total)) {
            $order['total'] = $total[0]['total'];
            $order['num_of_product'] = $total[0]['num_of_product'];
        } else {
            $order['total'] = 0;
            $order['num_of_product'] = 0;
        }

        $product = custom("SELECT product.id, product_variation.image,product.name,product_variation.color,product_variation.size,unit_price,quantity
            FROM `product`,`order_detail`,product_variation	
            WHERE `product_variation`.id = order_detail.product_variation_id
            And product.id = product_variation.product_id
            AND order_id = $order_id
            ");
        $order['product'] = $product;

        $res = $order;

        return $res;
    }
    function listOrder($status, $page, $perPage, $startDate, $endDate)
    {
        $offset = $perPage * ($page - 1);
        $total = custom(
            "SELECT COUNT(id) as total
            FROM (
                SELECT `tbl_order`.id
                FROM `tbl_order`
                WHERE `tbl_order`.status LIKE '%$status%'
                AND `tbl_order`.created_date > '$startDate' AND  `tbl_order`.created_date < '$endDate'
            ) AS B
        "
        );

        $check = ceil($total[0]['total'] / $perPage);

        $order = custom("
        SELECT `tbl_order`.id 
        FROM `tbl_order`
        WHERE  `tbl_order`.status LIKE '%$status%'
        AND `tbl_order`.created_date > '$startDate' AND  `tbl_order`.created_date < '$endDate'
        ORDER BY `tbl_order`.created_date DESC
        LIMIT $perPage  OFFSET $offset 
        ");

        foreach ($order as $key => $obj) {
            $order[$key] = $this->getDetail($obj['id'], '*');
        }
        $res['totalCount'] = $total[0]['total'];
        $res['numOfPage'] =  $check;
        $res['page'] = $page;
        $res['obj'] = $order;

        return $res;
    }
    function updateStatus($order_id, $status)
    {
        update('tbl_order', ['id' => $order_id], ['status' => $status]);
    }
}