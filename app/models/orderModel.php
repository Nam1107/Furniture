<?php

class orderModel extends Controllers
{
    public $shipping_model;
    public $delivery_model;
    public function __construct()
    {
        $this->shipping_model = $this->model('shippingModel');
        $this->delivery_model = $this->model('deliveryModel');
    }
    function getDetail($order_id)
    {
        $order = custom("
        SELECT `order`.id,`order`.created_date ,`user`.user_name,`order`.status , `order`.address,SUM(`order_detail`.unit_price*`order_detail`.quantity) AS total,  COUNT(`order_detail`.order_id) AS numOfProduct
        FROM `order`,`order_detail`	,`user`
        WHERE `order`.id = order_detail.order_id
        AND `order`.id = $order_id
        AND user.id = order.user_id
        GROUP BY
        `order_detail`.order_id
        ");

        if (!$order) {
            $this->loadErrors(400, 'No orders yet');
        }

        $shipping = $this->shipping_model->getList($order_id);

        $product = custom("SELECT product.id, product_variation.image,product.name,product_variation.color,product_variation.size,unit_price,quantity
        FROM `product`,`order_detail`,product_variation	
        WHERE `product_variation`.id = order_detail.product_variation_ID
        And product.id = product_variation.product_id
        AND order_id = $order_id
        ");
        $delivery = $this->delivery_model->getDetail($order_id);
        $res['obj'] = $order[0];
        $res['obj']['product'] = $product;
        $res['obj']['shipping'] = $shipping;
        $res['obj']['delivery'] = $delivery;

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
            $order[$key] = $this->getDetail($obj['id'])['obj'];
        }

        $res = $this->loadList($total[0]['total'], $check, $page, $order);

        return $res;
    }
    function myListOrder($userID, $status, $page, $perPage)
    {
        $offset = $perPage * ($page - 1);

        $total = custom(
            "SELECT COUNT(id) as total
            FROM (
                SELECT `order`.id
                FROM `order`
                WHERE `order`.status LIKE '%$status%'
                AND `order`.user_id = $userID
            ) AS B
        "
        );

        $check = ceil($total[0]['total'] / $perPage);

        $order = custom("
        SELECT `order`.id,`order`.status ,C.description,C.created_date AS lastUpdated, `order`.created_date ,SUM(`order_detail`.unit_price*`order_detail`.quantity) AS total,  COUNT(`order_detail`.order_id) AS numOfProduct
        FROM `order`,`order_detail`	,(
        SELECT shipping_detail.*
        FROM (SELECT max(id) AS curID
        from shipping_detail
        group by order_id) AS B, shipping_detail
        WHERE curID = id
        ) AS C
        WHERE `order`.id = order_detail.order_id
        AND `order`.user_id = $userID
        AND `order`.status like '%$status%'
        AND C.order_id = `order`.id
        GROUP BY
        `order_detail`.order_id
        LIMIT $perPage  OFFSET $offset 
        ");

        if (!$order) {
            $this->loadErrors(400, 'No orders yet');
        }

        foreach ($order as $key => $obj) {
            $val = $obj['id'];
            $order[$key]['product'] = custom("SELECT product.id, product.image,product.name,unit_price,quantity
            FROM `product`,`order_detail`	
            WHERE `product`.id = order_detail.product_variation_ID
            AND order_id = $val
            ");
        }
        $res['status'] = 1;
        $res['totalCount'] = $total[0]['total'];
        $res['numOfPage'] = $check;
        $res['obj'] = $order;
        return $res;
    }
    public function createOrder($userID, $note, $phone, $address)
    {
        $order['userID'] = $userID;
        $order['note'] = $note;
        $order['status'] = 'To Ship';
        $order['phone'] = $phone;
        $order['address'] = $address;
        $order['created_date'] = currentTime();

        $order_id = create('order', $order);
        return $order_id;
    }
    public function createOrderDetail($order_id, $product_id, $unit_price, $quantity)
    {
        $condition = [
            "order_id" => $order_id,
            "product_id" => $product_id,
            "unit_price" => $unit_price,
            "quantity" => $quantity,
            "created_date" => currentTime()
        ];
        create('order_detail', $condition);
    }
    public function updateStatus($order_id, $status, $description)
    {
        update('order', ['id' => $order_id], ['status' => $status]);
        $shipping = [
            "order_id" => $order_id,
            "description" => $description,
            "created_date" => currentTime()
        ];
        create('shipping_detail', $shipping);
    }
}