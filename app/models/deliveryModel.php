<?php

class deliveryModel extends Controllers
{
    public $user_model;
    public function __construct()
    {
        $this->user_model = $this->model('userModel');
    }
    function getDetail($orderid)
    {
        $delivery = custom("
                SELECT delivery_order.*
                FROM delivery_order
                WHERE delivery_order.order_id = $orderid
        ");

        $leaderid = !empty($delivery[0]['shipper_leader_id']) ?  $delivery[0]['shipper_leader_id'] : 0;

        $leader = $this->user_model->getDetail($leaderid, 'id,user_name,avatar,phone');

        $deliveryid  = !empty($delivery[0]['id']) ?  $delivery[0]['id'] : 0;

        $member = custom("
            SELECT user.id,user.user_name,user.avatar,user.phone
            FROM delivery_order_member,user
            WHERE delivery_order_member.delivery_order_id = $deliveryid
            AND user.id = delivery_order_member.shipper_member_id

        ");
        $delivery = empty($delivery) ? null : $delivery[0];
        $member = empty($member) ? null : $member;
        $res['detail'] = $delivery;
        $res['leader'] = $leader;
        $res['member'] = $member;
        return ($res);
    }

    function create()
    {
    }
}