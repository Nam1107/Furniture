<?php

class orderModel extends Controllers
{
    public $shipping_model;
    public function __construct()
    {
        $this->shipping_model = $this->model('shippingModel');
    }
    function getDetail($orderID)
    {
        $order = custom("
        SELECT `order`.ID,`order`.status , `order`.createdAt ,SUM(`orderDetail`.unitPrice*`orderDetail`.quantity) AS total,  COUNT(`orderDetail`.orderID) AS numOfProduct
        FROM `order`,`orderDetail`	
        WHERE `order`.ID = orderDetail.orderID
        AND `order`.ID = $orderID
        GROUP BY
        `orderDetail`.orderID
        ");

        if (!$order) {
            $this->loadErrors(400, 'No orders yet');
        }

        $shipping = $this->shipping_model->getList($orderID);

        $product = custom("SELECT product.ID, product.image,product.name,unitPrice,quantity
        FROM `product`,`orderDetail`	
        WHERE `product`.ID = orderDetail.productID
        AND orderID = $orderID
        ");

        $res['status'] = 1;
        $res['obj'] = $order[0];
        $res['obj']['shipping'] = $shipping;
        $res['obj']['product'] = $product;
        return $res;
    }
    function listOrder($status, $page, $perPage, $startDate, $endDate)
    {
        $offset = $perPage * ($page - 1);
        $total = custom(
            "SELECT COUNT(ID) as total
            FROM (
                SELECT `order`.ID
                FROM `order`
                WHERE `order`.status LIKE '%$status%'
                AND `order`.createdAt > '$startDate' AND  `order`.createdAt < '$endDate'
            ) AS B
        "
        );

        $check = ceil($total[0]['total'] / $perPage);

        $order = custom("
        SELECT `order`.ID,`order`.userID,user.name,`order`.status , `order`.createdAt ,SUM(`orderDetail`.unitPrice*`orderDetail`.quantity) AS total,  COUNT(`orderDetail`.orderID) AS numOfProduct
        FROM `order`,`orderDetail`,user
        WHERE `order`.ID = orderDetail.orderID
        AND `order`.status LIKE '%$status%'
        AND user.ID = `order`.userID
        AND `order`.createdAt > '$startDate' AND  `order`.createdAt < '$endDate'
        GROUP BY `orderDetail`.orderID
        ORDER BY `order`.createdAt DESC
        LIMIT $perPage  OFFSET $offset 
        ");

        $res = $this->loadList($total[0]['total'], $check, $page, $order);
        return $res;
    }
    function myListOrder($userID, $status, $page, $perPage)
    {
        $offset = $perPage * ($page - 1);

        $total = custom(
            "SELECT COUNT(ID) as total
            FROM (
                SELECT `order`.ID
                FROM `order`
                WHERE `order`.status LIKE '%$status%'
                AND `order`.userID = $userID
            ) AS B
        "
        );

        $check = ceil($total[0]['total'] / $perPage);

        $order = custom("
        SELECT `order`.ID,`order`.status ,C.description,C.createdAt AS lastUpdated, `order`.createdAt ,SUM(`orderDetail`.unitPrice*`orderDetail`.quantity) AS total,  COUNT(`orderDetail`.orderID) AS numOfProduct
        FROM `order`,`orderDetail`	,(
        SELECT shippingDetail.*
        FROM (SELECT max(ID) AS curID
        from shippingDetail
        group by orderID) AS B, shippingDetail
        WHERE curID = ID
        ) AS C
        WHERE `order`.ID = orderDetail.orderID
        AND `order`.userID = $userID
        AND `order`.status like '%$status%'
        AND C.orderID = `order`.ID
        GROUP BY
        `orderDetail`.orderID
        LIMIT $perPage  OFFSET $offset 
        ");

        if (!$order) {
            $this->loadErrors(400, 'No orders yet');
        }

        foreach ($order as $key => $obj) {
            $val = $obj['ID'];
            $order[$key]['product'] = custom("SELECT product.ID, product.image,product.name,unitPrice,quantity
            FROM `product`,`orderDetail`	
            WHERE `product`.ID = orderDetail.productID
            AND orderID = $val
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
        $order['createdAt'] = currentTime();

        $orderID = create('order', $order);
        return $orderID;
    }
    public function createOrderDetail($orderID, $productID, $unitPrice, $quantity)
    {
        $condition = [
            "orderID" => $orderID,
            "productID" => $productID,
            "unitPrice" => $unitPrice,
            "quantity" => $quantity,
            "createdAt" => currentTime()
        ];
        create('orderDetail', $condition);
    }
    public function updateStatus($orderID, $status, $description)
    {
        update('order', ['ID' => $orderID], ['status' => $status]);
        $shipping = [
            "orderID" => $orderID,
            "description" => $description,
            "createdAt" => currentTime()
        ];
        create('shippingDetail', $shipping);
    }
}