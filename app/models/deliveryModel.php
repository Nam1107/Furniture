<?php

class deliveryModel extends Controllers
{
    public $user_model;
    public function __construct()
    {
        $this->user_model = $this->model('userModel');
    }
    function getDetail($order_id, $value = '*', $member = 1, $gallery = 1)
    {
        $delivery = custom("
                SELECT $value
                FROM delivery_order
                WHERE delivery_order.order_id = $order_id
        ");

        $leader_id = !empty($delivery[0]['shipper_leader_id']) ?  $delivery[0]['shipper_leader_id'] : 0;

        $leader = $this->user_model->getDetail($leader_id, 'id,user_name,avatar,phone');

        $delivery_id  = !empty($delivery[0]['id']) ?  $delivery[0]['id'] : 0;

        $member = custom("
            SELECT user.id,user.user_name,user.avatar,user.phone
            FROM delivery_order_member,user
            WHERE delivery_order_member.delivery_order_id = $delivery_id
            AND user.id = delivery_order_member.shipper_member_id

        ");
        $departed_gallery = custom("
        SELECT * from departed_gallery where delivery_order_id = $delivery_id
        ");
        $delivered_gallery = custom("
        SELECT * from delivered_gallery where delivery_order_id = $delivery_id
        ");
        $delivery = empty($delivery) ? null : $delivery[0];
        $member = empty($member) ? null : $member;
        $res['detail'] = $delivery;
        $res['leader'] = $leader;
        $res['member'] = $member;
        $res['gallery'] = [
            'departed' => $departed_gallery,
            'delivered' => $delivered_gallery
        ];
        return ($res);
    }

    function create($order_id = null, $leader_id = null, $created_by = null)
    {
        $sent_vars = [
            'order_id' => $order_id,
            'shipper_leader_id' => $leader_id,
            'created_by' => $created_by,
            'departed_date' => currentTime(),
            'delivered_date' => null
        ];
        create('delivery_order', $sent_vars);
        $shipping = [
            "order_id" => $order_id,
            "description" => shipping_status[1],
            "created_date" => currentTime(),
            "created_by" => $created_by
        ];
        create('shipping_report', $shipping);
    }
}