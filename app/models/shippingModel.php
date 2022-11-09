<?php
class shippingModel
{
    function create($order_id, $userID = 0)
    {
        $shipping = [
            "order_id" => $order_id,
            "description" => "Order has been created",
            "created_date" => currentTime(),
            "created_by" => $userID
        ];
        create('shipping_detail', $shipping);
    }
    function getList($order_id)
    {
        $shipping = custom("SELECT shipping_detail.description,shipping_detail.created_date
        from shipping_detail
        WHERE order_id =  $order_id
        ");
        return $shipping;
    }
}