<?php

class orderModel extends Controllers
{
    public $shipping_model;
    public $delivery_model;
    public $user_model;
    public function __construct()
    {
        $this->user_model = $this->model('userModel');
        $this->shipping_model = $this->model('shippingModel');
        $this->delivery_model = $this->model('deliveryModel');
        set_error_handler(function ($severity, $message, $file, $line) {
            throw new ErrorException($message, 0, $severity, $file, $line);
        }, E_WARNING);
    }
    function getDetail($order_id, $value = '*', $shipping = 0, $product = 0, $delivery = 0)
    {

        $order = custom("
        SELECT $value
        FROM `order`
        WHERE `order`.id = $order_id
        ");

        $total = custom("
        SELECT SUM(`order_detail`.unit_price*`order_detail`.quantity) AS total,  SUM(`order_detail`.quantity) AS num_of_product
        FROM `order_detail`
        WHERE  order_detail.order_id= $order_id
        GROUP BY
        `order_detail`.order_id
        ");

        if (!$order) {
            $this->loadErrors(400, 'No orders yet');
        }

        $order = $order[0];
        $order['total'] = $total[0]['total'];
        $order['num_of_product'] = $total[0]['num_of_product'];

        $user_id = $order['user_id'];

        unset($order['user_id']);

        $order['user'] = $this->user_model->getDetail($user_id, 'id,avatar,user_name,phone,email');
        $res = $order;

        if ($shipping == 1) {
            $shipping = $this->shipping_model->getList($order_id);
            $res['shipping'] = $shipping;
        }

        if ($product == 1) {
            $product = custom("SELECT product.id, product_variation.image,product.name,product_variation.color,product_variation.size,unit_price,quantity
            FROM `product`,`order_detail`,product_variation	
            WHERE `product_variation`.id = order_detail.product_variation_ID
            And product.id = product_variation.product_id
            AND order_id = $order_id
            ");
            $res['product'] = $product;
        }

        if ($delivery == 1) {
            $delivery = $this->delivery_model->getDetail($order_id);
            $res['delivery'] = $delivery;
        }


        return $res;
    }
    function listOrder($status, $page, $perPage, $startDate, $endDate)
    {
        $offset = $perPage * ($page - 1);
        $total = custom(
            "SELECT COUNT(id) as total
            FROM (
                SELECT `order`.id
                FROM `order`
                WHERE `order`.status LIKE '%$status%'
                AND `order`.created_date > '$startDate' AND  `order`.created_date < '$endDate'
            ) AS B
        "
        );

        $check = ceil($total[0]['total'] / $perPage);

        $order = custom("
        SELECT `order`.id 
        FROM `order`
        WHERE  `order`.status LIKE '%$status%'
        AND `order`.created_date > '$startDate' AND  `order`.created_date < '$endDate'
        ORDER BY `order`.created_date DESC
        LIMIT $perPage  OFFSET $offset 
        ");


        foreach ($order as $key => $obj) {
            $order[$key] = $this->getDetail($obj['id'], '*');
        }

        $res = $this->loadList($total[0]['total'], $check, $page, $order);

        return $res;
    }


    public function updateStatus($order_id, $status, $description)
    {
        try {
            update('order', ['id' => $order_id], ['status' => $status]);
            $user_id = $_SESSION['user']['id'];
            $shipping = [
                "order_id" => $order_id,
                "description" => $description,
                "created_date" => currentTime(),
                "created_by" => $user_id
            ];
        } catch (ErrorException $e) {
            $this->loadErrors(400, $e->getMessage() . " on line " . $e->getLine() . " in file " . $e->getfile());
        }
        create('shipping_report', $shipping);
    }
}