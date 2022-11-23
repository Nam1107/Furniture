<?php

class deliveryController extends Controllers
{
    public $delivery_model;
    public $user_model;
    public $order_model;
    public $shipping_model;
    public function __construct()
    {
        $this->order_model = $this->model('orderModel');
        $this->delivery_model = $this->model('deliveryModel');
        $this->shipping_model = $this->model('shippingModel');
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
        $res['order'] = $this->order_model->getDetail($order_id, '*', 1);
        unset($res['order_id']);

        $customer_id = $res['order']['user_id'];
        $res['order']['customer'] = $this->user_model->getDetail($customer_id, 'id,avatar,user_name,phone,email', 0);
        unset($res['order']['user_id']);
        dd($res);
        exit;
    }

    function report($user_id = 0)
    {
        $this->middle_ware->checkRequest('GET');
        $this->middle_ware->shipperOnly();
        $sent_vars = $_GET;
        try {
            $startDate = $sent_vars['startDate'];
            $endDate = $sent_vars['endDate'];
        } catch (ErrorException $e) {
            $this->loadErrors(400, $e->getMessage() . " on line " . $e->getLine() . " in file " . $e->getfile());
        }
        $report = custom("SELECT status,COUNT(id) AS numOfDelivery
        FROM delivery_order
        WHERE delivery_order.shipper_id = $user_id 
        AND delivery_order.departed_date > '$startDate' AND  delivery_order.departed_date < '$endDate'
        GROUP BY delivery_order.`status`");

        $status = array_column($report, 'status');
        $res = array();
        foreach (delivery_status as $key => $val) {
            $check = $this->find(delivery_status[$key], $status);
            if ($check !== null) {
                $value['status'] = delivery_status[$key];
                $value['numOfDelivery'] = $report[$check]['numOfDelivery'];
            } else {
                $value['status'] = delivery_status[$key];
                $value['numOfDelivery'] = 0;
            }
            array_push($res, $value);
        }
        dd($res);
        exit;
    }

    function listStatus()
    {
        $this->middle_ware->checkRequest('GET');
        dd(delivery_status);
        exit;
    }
    function reasonFail()
    {
        $this->middle_ware->checkRequest('GET');
        dd(shipping_fail);
        exit;
    }
    function listByStatus()
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
    function listByShipper()
    {
        $this->middle_ware->checkRequest('GET');
        $this->middle_ware->shipperOnly();
        $sent_vars = $_GET;
        try {
            $user_id = $sent_vars['userID'];
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
            $this->shipping_model->create($order_id, $shipper_id, shipping_status[1]);
            $delivery_id = $this->delivery_model->create($order_id, $shipper_id);
        } catch (ErrorException $e) {
            $this->loadErrors(400, $e->getMessage() . " on line " . $e->getLine() . " in file " . $e->getfile());
        }
        $res['delivery_id'] = $delivery_id;
        $res['msg'] = 'Thành công';
        dd($res);
        exit;
    }
    // function updateDelivery($delivery_id = 0)
    // {
    //     $this->middle_ware->checkRequest('PUT');
    //     $this->middle_ware->shipperOnly();

    //     $json = file_get_contents("php://input");
    //     $sent_vars = json_decode($json, TRUE);

    //     $role = $_SESSION['user']['role'];
    //     $user_id = $_SESSION['user']['id'];

    //     $delivery = $this->delivery_model->getDetail($delivery_id);
    //     if (!$delivery) {
    //         $this->loadErrors(404, "Không tìm thấy đơn hàng");
    //     }

    //     if (!in_array("ROLE_ADMIN", $role)) {

    //         if ($user_id != $delivery['shipper_id']) {
    //             $this->loadErrors(400, 'Bạn không có quyền sửa đơn vận');
    //         }
    //     }

    //     try {
    //         $shipper_id = $sent_vars['shipper_id'];
    //         $status = $sent_vars['status'];
    //         if (!in_array($status, delivery_status)) {
    //             $this->loadErrors(400, 'Trạng thái đơn vận không hợp lệ');
    //         }
    //         $description = $sent_vars['description'];
    //         $this->delivery_model->update($delivery_id, $shipper_id, $status, $description);
    //     } catch (ErrorException $e) {
    //         $this->loadErrors(400, $e->getMessage() . " on line " . $e->getLine() . " in file " . $e->getfile());
    //     }
    //     $res['msg'] = 'Thành công';
    //     dd($res);
    //     exit;
    // }
    function completeDelivery($delivery_id = 0)
    {
        $this->middle_ware->checkRequest('PUT');
        $this->middle_ware->shipperOnly();

        $role = $_SESSION['user']['role'];
        $user_id = $_SESSION['user']['id'];
        $delivery = $this->delivery_model->getDetail($delivery_id);

        $status = $delivery['status'];
        if ($status != delivery_status[0]) {
            $status = delivery_status[0];
            $this->loadErrors(400, "Đơn vận không trong trạng thái '$status'");
        }
        if (!$delivery) {
            $this->loadErrors(404, "Không tìm thấy đơn hàng");
        }
        if (!in_array("ROLE_ADMIN", $role)) {
            if ($user_id != $delivery['shipper_id']) {
                $this->loadErrors(400, 'Bạn không có quyền sửa đơn vận');
                exit;
            }
        }
        try {
            $description = "Giao hàng thành công";
            $this->delivery_model->update($delivery_id, delivery_status[1], $description, currentTime());
            $this->order_model->updateStatus($delivery['order_id'], status_order[2]);
            $this->shipping_model->create($delivery['order_id'], $user_id, $description);
        } catch (ErrorException $e) {
            $this->loadErrors(400, $e->getMessage() . " on line " . $e->getLine() . " in file " . $e->getfile());
        }
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

        $role = $_SESSION['user']['role'];
        $user_id = $_SESSION['user']['id'];
        $delivery = $this->delivery_model->getDetail($delivery_id);
        $status = $delivery['status'];
        if ($status != delivery_status[0]) {
            $status = delivery_status[0];
            $this->loadErrors(400, "Đơn vận không trong trạng thái '$status'");
        }
        if (!$delivery) {
            $this->loadErrors(404, "Không tìm thấy đơn hàng");
        }
        if (!in_array("ROLE_ADMIN", $role)) {
            if ($user_id != $delivery['shipper_id']) {
                $this->loadErrors(400, 'Bạn không có quyền sửa đơn vận');
            }
        }

        try {
            $description = $sent_vars['description'];
            if (!in_array($description, shipping_fail)) {
                $this->loadErrors(400, 'Lý do hủy không hợp lệ');
            }
            $this->delivery_model->update($delivery_id, delivery_status[2], $description, null);
            $this->shipping_model->create($delivery['order_id'], $user_id, $description);
            $this->order_model->updateStatus($delivery['order_id'], status_order[0]);
        } catch (ErrorException $e) {
            $this->loadErrors(400, $e->getMessage() . " on line " . $e->getLine() . " in file " . $e->getfile());
        }
        $res['msg'] = 'Thành công';
        dd($res);
        exit;
    }
}