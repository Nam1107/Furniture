<?php

class order extends Controllers
{
    public $validate_user;
    public $middle_ware;
    public $order_model;
    public $delivery_model;
    public function __construct()
    {
        $this->order_model = $this->model('orderModel');
        $this->delivery_model = $this->model('deliveryModel');
        $this->cart_model = $this->model('cartModel');
        $this->shipping_model = $this->model('shippingModel');
        $this->middle_ware = new middleware();
    }

    public function createOrder()
    {
        # code...
        $this->middle_ware->checkRequest('POST');
        $this->middle_ware->userOnly();

        $user_id = $_SESSION['user']['id'];
        $json = file_get_contents("php://input");
        $sent_vars = json_decode($json, TRUE);

        #check...
        if (!isset($sent_vars['note']) || empty($sent_vars['address'])) {
            $this->loadErrors(400, 'Error: input is invalid');
        }
        $cart = $this->cart_model->getCart($user_id)['obj'];
        if (!$cart) {
            $this->loadErrors(400, 'Your cart is empty');
        }
        foreach ($cart as $key => $val) {
            if ($val['status'] === 0) {
                $this->loadErrors(400, 'Some items in your cart has sold out');
            }
        }

        #update sold of product
        foreach ($cart as $key => $val) {
            $quantity = $val['quantity'];
            $product_id = $val['product_id'];
            custom("
            UPDATE product SET stock = if(stock < $quantity,0, stock - $quantity), sold = if(sold IS NULL, $quantity , sold + $quantity) WHERE id = $product_id
            ");
        }
        #delete cart
        $this->cart_model->delete($user_id);

        #create order
        $order_id = $this->order_model->createOrder($user_id, $sent_vars['note'],  $sent_vars['address']);

        $this->shipping_model->create($order_id);

        foreach ($cart as $key => $val) {
            $this->order_model->createOrderDetail($order_id, $val['product_id'], $val['unitPrice'], $val["quantity"]);
        }
        $res = $this->order_model->getDetail($order_id);
        dd($res);
        exit();
    }

    public function myListOrder()
    {
        $this->middle_ware->checkRequest('GET');
        $this->middle_ware->userOnly();
        $user_id = $_SESSION['user']['id'];

        $sent_vars = $_GET;

        try {
            $status = $sent_vars['status'];
            $page = $sent_vars['page'];
            $perPage = $sent_vars['perPage'];
        } catch (Error $e) {
            $this->loadErrors(400, 'Lỗi biến đầu vào');
        }

        $res = $this->order_model->myListOrder($user_id, $status, $page, $perPage);
        dd($res);
        exit();
    }

    public function adminListOrder()
    {
        $this->middle_ware->checkRequest('GET');
        $this->middle_ware->adminOnly();
        $sent_vars = $_GET;
        try {
            $status = $sent_vars['status'];
            $startDate = $sent_vars['startDate'];
            $endDate = $sent_vars['endDate'];
            $page = $sent_vars['page'];
            $perPage = $sent_vars['perPage'];
        } catch (Error $e) {
            $this->loadErrors(400, 'Lỗi biến đầu vào');
        }

        $res = $this->order_model->listOrder($status, $page, $perPage, $startDate, $endDate);
        dd($res);
        exit();
    }

    public function getMyOrder($id = 0)
    {
        $this->middle_ware->checkRequest('GET');
        $this->middle_ware->userOnly();
        $res = $this->order_model->getDetail($id);
        dd($res);
        exit();
    }

    public function adminGetOrder($id = 0)
    {
        $this->middle_ware->checkRequest('GET');
        $this->middle_ware->adminOnly();
        $res = $this->order_model->getDetail($id, '*', 1, 1, 1);
        dd($res);
        exit();
    }

    public function setStatus($id = 0)
    {
        $this->middle_ware->checkRequest('PUT');
        $this->middle_ware->adminOnly();

        $order = selectOne('order', ['id' => $id]);
        if (!$order) {
            $this->loadErrors(400, 'Không tìm thấy đơn hàng');
        }

        $json = file_get_contents("php://input");
        $sent_vars = json_decode($json, TRUE);

        if (empty($sent_vars['status']) || empty($sent_vars['description'])) {
            $this->loadErrors(400, 'Lỗi biến đầu vào');
        }

        $this->order_model->updateStatus($id, $sent_vars['status'], $sent_vars['description']);
        $res['status'] = 1;
        $res['msg'] = 'Success';
        dd($res);
        exit();
    }

    public function cancelOrder($id = 0)
    {
        $this->middle_ware->checkRequest('PUT');
        $this->middle_ware->userOnly();

        $status = status_order[5];
        $order = selectOne('order', ['id' => $id]);
        $json = file_get_contents("php://input");
        $sent_vars = json_decode($json, TRUE);
        $reason = $sent_vars['reason'];
        $reason = "Lý do hủy: " . $reason;
        if (!isset($sent_vars['reason'])) {
            $this->loadErrors(400, 'Lỗi biến đầu vào');
        }
        if (!$order) {
            $this->loadErrors(400, 'Không tìm thấy đơn hàng');
        }
        switch ($order['status']) {
            case 'To Ship':
                $this->order_model->updateStatus($id, $status, $reason);
                $res['status'] = 1;
                $res['msg'] = 'Success';
                dd($res);
                exit();
                break;
            case 'To Recivie':
                $this->loadErrors(400, 'Đơn hàng đang được vận chuyển');
                exit;
                break;
            default:
                $this->loadErrors(400, 'Đơn hàng đã được giao');
                exit;
                break;
        }
    }
    public function orderRecevied($id = 0)
    {
        $this->middle_ware->checkRequest('PUT');
        $this->middle_ware->userOnly();

        $status = 'To Rate';
        $order = selectOne('order', ['id' => $id]);
        if (!$order) {
            $this->loadErrors(400, 'Không tìm thấy đơn hàng');
            exit();
        }

        switch ($order['status']) {
            case 'To Recivie':
                $this->order_model->updateStatus($id, $status, "Người dùng xác nhận: Đã nhận được hàng");
                $res['status'] = 1;
                $res['msg'] = 'Success';
                dd($res);
                exit();
            case 'To Ship':
                $this->loadErrors(400, 'Đơn hàng đang chờ vận chuyển');
                exit();
            default:
                $this->loadErrors(400, 'Đơn hàng đã hoàn thành');
                exit();
        }
    }
}