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
        create('shipping_report', $shipping);
    }
    function getList($order_id)
    {
        $shipping = custom("SELECT shipping_report.description,shipping_report.created_date
        from shipping_report
        WHERE order_id =  $order_id
        ");
        return $shipping;
    }
}