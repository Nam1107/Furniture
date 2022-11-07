<?php

class order extends Controllers
{
    public $validate_user;
    public $middle_ware;
    public $order_model;
    public function __construct()
    {
        $this->order_model = $this->model('orderModel');
        $this->cart_model = $this->model('cartModel');
        $this->shipping_model = $this->model('shippingModel');
        $this->middle_ware = new middleware();
        set_error_handler(function ($err_severity, $err_msg, $err_file, $err_line, array $err_context) {
            throw new ErrorException($err_msg, 0, $err_severity, $err_file, $err_line);
        }, E_WARNING);
    }

    public function createOrder()
    {
        # code...
        $this->middle_ware->checkRequest('POST');
        $this->middle_ware->userOnly();

        $userID = $_SESSION['user']['ID'];
        $json = file_get_contents("php://input");
        $sent_vars = json_decode($json, TRUE);

        #check...
        if (!isset($sent_vars['note']) || empty($sent_vars['phone']) || empty($sent_vars['address'])) {
            $this->loadErrors(400, 'Error: input is invalid');
        }
        $cart = $this->cart_model->getCart($userID)['obj'];
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
            $productID = $val['productID'];
            custom("
            UPDATE product SET stock = if(stock < $quantity,0, stock - $quantity), sold = if(sold IS NULL, $quantity , sold + $quantity) WHERE ID = $productID
            ");
        }
        #delete cart
        $this->cart_model->delete($userID);

        #create order
        $orderID = $this->order_model->createOrder($userID, $sent_vars['note'], $sent_vars['phone'], $sent_vars['address']);

        $this->shipping_model->create($orderID);

        foreach ($cart as $key => $val) {
            $this->order_model->createOrderDetail($orderID, $val['productID'], $val['unitPrice'], $val["quantity"]);
        }
        $res = $this->order_model->getDetail($orderID);
        dd($res);
        exit();
    }

    public function myListOrder()
    {
        $this->middle_ware->checkRequest('GET');
        $this->middle_ware->userOnly();
        $userID = $_SESSION['user']['ID'];

        // $json = file_get_contents("php://input");
        // $sent_vars = json_decode($json, TRUE);

        $sent_vars = $_GET;

        try {
            $status = $sent_vars['status'];
            $page = $sent_vars['page'];
            $perPage = $sent_vars['perPage'];
        } catch (Error $e) {
            $this->loadErrors(400, 'Error: input is invalid');
        }

        $res = $this->order_model->myListOrder($userID, $status, $page, $perPage);
        dd($res);
        exit();
    }

    public function adminListOrder()
    {
        $this->middle_ware->checkRequest('GET');
        $this->middle_ware->adminOnly();
        // $json = file_get_contents("php://input");
        // $sent_vars = json_decode($json, TRUE);
        $sent_vars = $_GET;
        try {
            $status = $sent_vars['status'];
            $startDate = $sent_vars['startDate'];
            $endDate = $sent_vars['endDate'];
            $page = $sent_vars['page'];
            $perPage = $sent_vars['perPage'];
        } catch (Error $e) {
            $this->loadErrors(400, 'Error: input is invalid');
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
        $res = $this->order_model->getDetail($id);
        dd($res);
        exit();
    }

    public function setStatusOrder($id = 0)
    {
        $this->middle_ware->checkRequest('PUT');
        $this->middle_ware->adminOnly();

        $order = selectOne('order', ['ID' => $id]);
        if (!$order) {
            $this->loadErrors(400, 'No orders yet');
        }

        $json = file_get_contents("php://input");
        $sent_vars = json_decode($json, TRUE);

        if (empty($sent_vars['status']) || empty($sent_vars['description'])) {
            $this->loadErrors(400, 'Not enough value');
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

        $status = 'Cancelled';
        $order = selectOne('order', ['ID' => $id]);
        $json = file_get_contents("php://input");
        $sent_vars = json_decode($json, TRUE);
        $reason = $sent_vars['reason'];
        $reason = "Reason for Cancellation : " . $reason;
        if (!isset($sent_vars['reason'])) {
            $this->loadErrors(400, 'Error: input is invalid');
        }
        if (!$order) {
            $this->loadErrors(400, 'No orders yet');
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
                $this->loadErrors(400, 'The order is being shipped');
                exit;
                break;
            default:
                $this->loadErrors(400, 'The order has been delivered');
                exit;
                break;
        }
    }
    public function orderRecevied($id = 0)
    {
        $this->middle_ware->checkRequest('PUT');
        $this->middle_ware->userOnly();

        $status = 'To Rate';
        $order = selectOne('order', ['ID' => $id]);
        if (!$order) {
            $this->loadErrors(400, 'No orders yet');
            exit();
        }

        switch ($order['status']) {
            case 'To Recivie':
                $this->order_model->updateStatus($id, $status, "Confirm Receipt of an Order from a Customer");
                $res['status'] = 1;
                $res['msg'] = 'Success';
                dd($res);
                exit();
            case 'To Ship':
                $this->loadErrors(400, 'Orders are being prepared');
                exit();
            default:
                $this->loadErrors(400, 'The order has been completed');
                exit();
        }
    }
}