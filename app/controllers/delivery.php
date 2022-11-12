<?php

class delivery extends Controllers
{
    public $delivery_model;
    public $user_model;
    public $order_model;
    public function __construct()
    {
        $this->order_model = $this->model('orderModel');
        $this->delivery_model = $this->model('deliveryModel');
        $this->user_model = $this->model('userModel');
        $this->middle_ware = new middleware();
        set_error_handler(function ($severity, $message, $file, $line) {
            throw new ErrorException($message, 0, $severity, $file, $line);
        }, E_WARNING);
    }
    function getDetail($orderID = 0)
    {
        $res = $this->delivery_model->getDetail($orderID);
        dd($res);
        exit;
    }
    function createDelivery()
    {
        $this->middle_ware->checkRequest('POST');
        $this->middle_ware->adminOnly();

        $user_id = $_SESSION['user']['id'];
        $json = file_get_contents("php://input");
        $sent_vars = json_decode($json, TRUE);
        try {
            $order_id = $sent_vars['order_id'];
            $order = $this->order_model->getDetail($order_id);
            if ($order['status'] != status_order[0]) {
                $this->loadErrors(400, "Đơn hàng không trong trạng thái 'Chờ vận chuyển'");
            }
            $order_id = $sent_vars['order_id'];
            $leader_id = $sent_vars['shipper_leader_id'];
            $created_by = $user_id;

            $this->delivery_model->create($order_id, $leader_id, $created_by);
        } catch (ErrorException $e) {
            $this->loadErrors(400, $e->getMessage() . " on line " . $e->getLine() . " in file " . $e->getfile());
        }
    }
}