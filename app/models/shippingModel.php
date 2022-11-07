<?php
class shippingModel
{
    function create($orderID, $userID = 0)
    {
        $shipping = [
            "orderID" => $orderID,
            "description" => "Order has been created",
            "createdAt" => currentTime(),
            "createdBy" => $userID
        ];
        create('shippingDetail', $shipping);
    }
    function getList($orderID)
    {
        $shipping = custom("SELECT shippingDetail.description,shippingDetail.createdAt
        from shippingDetail
        WHERE orderID =  $orderID
        ");
        return $shipping;
    }
}