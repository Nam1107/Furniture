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
    function getDetail($delivery_id = 0)
    {
        $this->middle_ware->checkRequest('GET');
        $this->middle_ware->shipperOnly();
        $res = $this->delivery_model->getDetail($delivery_id, '*', 1);

        if (empty($res)) {
            $this->loadErrors(404, 'Không tìm thấy đơn vận');
        }

        $user_id = $res['shipper_id'];
        $res['shipper'] = $this->user_model->getDetail($user_id, 'id,avatar,user_name,phone,email', 0);
        unset($res['shipper_id']);

        $admin_id = $res['created_by'];
        $res['admin'] = $this->user_model->getDetail($admin_id, 'id,avatar,user_name,phone,email', 0);
        unset($res['created_by']);

        $order_id = $res['order_id'];
        $res['order'] = $this->order_model->getDetail($order_id, '*', 0);
        unset($res['order_id']);

        $customer_id = $res['order']['user_id'];
        $res['order']['customer'] = $this->user_model->getDetail($customer_id, 'id,avatar,user_name,phone,email', 0);
        unset($res['order']['user_id']);
        dd($res);
        exit;
    }
    function shipperListDelivery()
    {
        $this->middle_ware->checkRequest('GET');
        $this->middle_ware->shipperOnly();
        $user_id = $_SESSION['user']['id'];
        $sent_vars = $_GET;
        try {
            $status = $sent_vars['status'];
            $startDate = $sent_vars['startDate'];
            $endDate = $sent_vars['endDate'];
            $page = $sent_vars['page'];
            $perPage = $sent_vars['perPage'];
        } catch (ErrorException $e) {
            $this->loadErrors(400, $e->getMessage() . " on line " . $e->getLine() . " in file " . $e->getfile());
        }
        $res = $this->delivery_model->getListByShipper($user_id, $status, $page, $perPage, $startDate, $endDate);
        foreach ($res['obj'] as $key => $each) {
            $order_id = empty($each['order_id']) ? 0 : $each['order_id'];
            $res['obj'][$key]['order'] = $this->order_model->getDetail($order_id, '*', 0);
            unset($res['obj'][$key]['order_id']);
        }
        dd($res);
    }
    function createDelivery()
    {
        $this->middle_ware->checkRequest('POST');
        $this->middle_ware->adminOnly();


        $json = file_get_contents("php://input");
        $sent_vars = json_decode($json, TRUE);


        try {
            $order_id = $sent_vars['order_id'];
            $order = $this->order_model->getDetail($order_id);
            if (!$order) {
                $this->loadErrors(404, 'Không tìm thấy đơn hàng');
            }
            if ($order['status'] != status_order[0]) {
                $status = status_order[0];
                $this->loadErrors(400, "Đơn hàng không trong trạng thái '$status'");
            }
            // $order_id = $sent_vars['order_id'];
            $shipper_id = $sent_vars['shipper_id'];

            $this->delivery_model->create($order_id, $shipper_id);
        } catch (ErrorException $e) {
            $this->loadErrors(400, $e->getMessage() . " on line " . $e->getLine() . " in file " . $e->getfile());
        }

        $res['status'] = 1;
        $res['obj'] = 'Thành công';
        dd($res);
        exit;
    }
    function updateDelivery($delivery_id = 0)
    {
        $this->middle_ware->checkRequest('PUT');
        $this->middle_ware->shipperOnly();

        $json = file_get_contents("php://input");
        $sent_vars = json_decode($json, TRUE);

        try {

            $shipper_id = $sent_vars['shipper_id'];
            // $delivered_date = $sent_vars['delivered_date'];
            $status = $sent_vars['status'];
            if (!in_array($status, delivery_status)) {
                $this->loadErrors(400, 'Trạng thái đơn vận không hợp lệ');
            }
            $description = $sent_vars['description'];
            $this->delivery_model->update($delivery_id, $shipper_id, $status, $description);
        } catch (ErrorException $e) {
            $this->loadErrors(400, $e->getMessage() . " on line " . $e->getLine() . " in file " . $e->getfile());
        }
        $res['status'] = 1;
        $res['msg'] = 'Thành công';
        dd($res);
        exit;
    }
    function completeDelivery($delivery_id = 0)
    {
        $this->middle_ware->checkRequest('PUT');
        $this->middle_ware->shipperOnly();

        $json = file_get_contents("php://input");
        $sent_vars = json_decode($json, TRUE);
        try {
            $description = $sent_vars['description'];
            $this->delivery_model->setSuccess($delivery_id, $description);
        } catch (ErrorException $e) {
            $this->loadErrors(400, $e->getMessage() . " on line " . $e->getLine() . " in file " . $e->getfile());
        }
        $res['status'] = 1;
        $res['msg'] = 'Thành công';
        dd($res);
        exit;
    }
    function cancelDelivery($delivery_id = 0)
    {
        $this->middle_ware->checkRequest('PUT');
        $this->middle_ware->shipperOnly();

        $json = file_get_contents("php://input");
        $sent_vars = json_decode($json, TRUE);

        try {
            $description = $sent_vars['description'];
            if (!in_array($description, shipping_fail)) {
                $this->loadErrors(400, 'Lý do hủy không hợp lệ');
            }
            $this->delivery_model->setFail($delivery_id, $description);
        } catch (ErrorException $e) {
            $this->loadErrors(400, $e->getMessage() . " on line " . $e->getLine() . " in file " . $e->getfile());
        }
        $res['status'] = 1;
        $res['msg'] = 'Thành công';
        dd($res);
        exit;
    }
}