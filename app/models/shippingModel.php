<?php
class shippingModel extends Controllers
{
    function create($order_id, $userID = 0)
    {
        $shipping = [
            "order_id" => $order_id,
            "description" => "Order has been created",
            "created_date" => currentTime(),
            "created_by" => $userID
        ];
        create('shipping_report', $shipping);
    }
    function getList($order_id = 0, $value = 'description,created_by,created_date')
    {
        $shipping = custom("SELECT $value
        from shipping_report
        WHERE order_id =  $order_id
        ");


        return $shipping;
    }
}