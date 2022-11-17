<?php

class order extends Controllers
{
    public $validate_user;
    public $middle_ware;
    public $order_model;
    public $delivery_model;
    public $user_model;
    public function __construct()
    {
        $this->order_model = $this->model('orderModel');
        $this->delivery_model = $this->model('deliveryModel');
        $this->cart_model = $this->model('cartModel');
        $this->shipping_model = $this->model('shippingModel');
        $this->user_model = $this->model('userModel');
        $this->middle_ware = new middleware();
        set_error_handler(function ($severity, $message, $file, $line) {
            throw new ErrorException($message, 0, $severity, $file, $line);
        }, E_WARNING);
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
    function listStatus()
    {
        $this->middle_ware->checkRequest('GET');
        dd(status_order);
        exit();
    }

    function orderFail()
    {
        $this->middle_ware->checkRequest('GET');
        dd(order_fail);
        exit;
    }

    function updateOrder($order_id)
    {
        $this->middle_ware->checkRequest('PUT');
        $this->middle_ware->adminOnly();
        //     "note": "không có gì",
        // "address": "Hà Nội",
        $json = file_get_contents("php://input");
        $sent_vars = json_decode($json, TRUE);

        $order = $this->order_model->getDetail($order_id, '*');
        if (!$order) {
            $this->loadErrors(404, "Không tìm thấy đơn hàng");
        }
        if ($order['status'] != status_order[0]) {
            $this->loadErrors(404, "Đơn hàng không trong trạng thái " . status_order[0]);
        }
        try {
            $note = $sent_vars['note'];
            $address = $sent_vars['address'];
            $this->order_model->update($order_id, $note, $address);
        } catch (ErrorException $e) {
            $this->loadErrors(400, $e->getMessage() . " on line " . $e->getLine() . " in file " . $e->getfile());
        }
        $res['status'] = 1;
        $res['msg'] = 'Thành công';
        dd($res);
        exit;
    }

    function report()
    {
        $this->middle_ware->checkRequest('GET');
        $this->middle_ware->adminOnly();
        $sent_vars = $_GET;
        try {
            $startDate = $sent_vars['startDate'];
            $endDate = $sent_vars['endDate'];
        } catch (ErrorException $e) {
            $this->loadErrors(400, $e->getMessage() . " on line " . $e->getLine() . " in file " . $e->getfile());
        }
        $report = custom("
        SELECT A.status,SUM(A.total) AS total,COUNT(A.id) AS numOfOrder,SUM(numOfProduct) AS numOfProduct
        FROM 
        (SELECT `tbl_order`.id,`tbl_order`.status,`tbl_order`.created_date,SUM(unit_price*quantity) AS total,SUM(quantity) AS numOfProduct
        FROM order_detail,`tbl_order`
        WHERE order_id = `tbl_order`.id
        AND `tbl_order`.created_date > '$startDate' AND  `tbl_order`.created_date < '$endDate'
        GROUP BY order_id) AS A
        GROUP BY A.status");

        // $res = in_array(status_order[0], $report['status']);
        $status = array_column($report, 'status');
        // $res = $this->find(status_order[0], $status);

        // $res = empty($res);

        $res = array();

        // $key = 1;

        foreach (status_order as $key => $val) {
            $check = $this->find(status_order[$key], $status);
            if ($check !== null) {
                $value['status'] = status_order[$key];
                $value['total'] = $report[$check]['total'];
                $value['numOfOrder'] = $report[$check]['numOfOrder'];
                $value['numOfProduct'] = $report[$check]['numOfProduct'];
            } else {
                $value['status'] = status_order[$key];
                $value['total'] = 0;
                $value['numOfOrder'] = 0;
                $value['numOfProduct'] = 0;
            }
            array_push($res, $value);
        }
        dd($res);

        exit;
    }

    public function listOrder()
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
        } catch (ErrorException $e) {
            $this->loadErrors(400, $e->getMessage() . " on line " . $e->getLine() . " in file " . $e->getfile());
        }

        $res = $this->order_model->listOrder($status, $page, $perPage, $startDate, $endDate);
        foreach ($res['obj'] as $key => $each) {
            $user_id = empty($each['user_id']) ? 0 : $each['user_id'];
            $res['obj'][$key]['customer'] = $this->user_model->getDetail($user_id, 'id,avatar,user_name,phone,email', 0);
            unset($res['obj'][$key]['user_id']);
        }
        dd($res);
        exit();
    }


    public function getOrder($order_id = 0)
    {
        $this->middle_ware->checkRequest('GET');
        $this->middle_ware->adminOnly();
        $res = $this->order_model->getDetail($order_id, '*', 1);

        if (!$res) {
            $this->loadErrors(404, 'Không tìm thấy đơn hàng');
        }

        $user_id = $res['user_id'];
        unset($res['user_id']);
        $res['customer'] = $this->user_model->getDetail($user_id, 'id,avatar,user_name,phone,email');

        $shipping = $this->shipping_model->getList($order_id);
        foreach ($shipping as $key => $each) {
            $user_id = empty($each['created_by']) ? 0 : $each['created_by'];
            unset($shipping[$key]['created_by']);
            $shipping[$key]['created_by'] = $this->user_model->getDetail($user_id, 'id,avatar,user_name,phone,email', 0);
        }
        $res['shipping'] = $shipping;

        $product = custom("SELECT product.id, product_variation.image,product.name,product_variation.color,product_variation.size,unit_price,quantity
            FROM `product`,`order_detail`,product_variation	
            WHERE `product_variation`.id = order_detail.product_variation_ID
            And product.id = product_variation.product_id
            AND order_id = $order_id
            ");
        $res['product'] = $product;

        $delivery = $this->delivery_model->getListByOrder($order_id, '*', 1);
        foreach ($delivery as $key => $each) {
            $user_id = empty($each['shipper_id']) ? 0 : $each['shipper_id'];
            unset($delivery[$key]['shipper_id']);
            $delivery[$key]['shipper'] = $this->user_model->getDetail($user_id, 'id,avatar,user_name,phone,email', 0);

            $user_id = empty($each['created_by']) ? 0 : $each['created_by'];
            unset($delivery[$key]['created_by']);
            $delivery[$key]['created_by'] = $this->user_model->getDetail($user_id, 'id,avatar,user_name,phone,email', 0);
        }
        $res['delivery'] = $delivery;

        dd($res);
        exit();
    }

    public function cancelOrder($order_id = 0)
    {
        $this->middle_ware->checkRequest('PUT');
        $this->middle_ware->adminOnly();

        $json = file_get_contents("php://input");
        $sent_vars = json_decode($json, TRUE);

        $order = $this->order_model->getDetail($order_id);

        if (!$order) {
            $this->loadErrors(404, 'Không tìm thấy đơn hàng');
        }

        try {
            if ($order['status'] != status_order[0]) {
                $this->loadErrors(404, 'Trạng thái đơn hàng không hợp lệ');
            }
            $description = $sent_vars['description'];
            if (!in_array($description, order_fail)) {
                $this->loadErrors(400, 'Lý do hủy không hợp lệ');
            }
            $this->order_model->cancel($order_id, $description);
        } catch (ErrorException $e) {
            $this->loadErrors(400, $e->getMessage() . " on line " . $e->getLine() . " in file " . $e->getfile());
        }
        $res['status'] = 1;
        $res['msg'] = 'Thành công';
        dd($res);
        exit;
    }

    // public function cancelOrder($id = 0)
    // {
    //     $this->middle_ware->checkRequest('PUT');
    //     $this->middle_ware->userOnly();

    //     $status = status_order[5];
    //     $order = selectOne('order', ['id' => $id]);
    //     $json = file_get_contents("php://input");
    //     $sent_vars = json_decode($json, TRUE);
    //     $reason = $sent_vars['reason'];
    //     $reason = "Lý do hủy: " . $reason;
    //     if (!isset($sent_vars['reason'])) {
    //         $this->loadErrors(400, 'Lỗi biến đầu vào');
    //     }
    //     if (!$order) {
    //         $this->loadErrors(400, 'Không tìm thấy đơn hàng');
    //     }
    //     switch ($order['status']) {
    //         case 'To Ship':
    //             $this->order_model->updateStatus($id, $status, $reason);
    //             $res['status'] = 1;
    //             $res['msg'] = 'Success';
    //             dd($res);
    //             exit();
    //             break;
    //         case 'To Recivie':
    //             $this->loadErrors(400, 'Đơn hàng đang được vận chuyển');
    //             exit;
    //             break;
    //         default:
    //             $this->loadErrors(400, 'Đơn hàng đã được giao');
    //             exit;
    //             break;
    //     }
    // }
    public function orderRecevied($id = 0)
    {
        $this->middle_ware->checkRequest('PUT');
        $this->middle_ware->userOnly();

        $status = 'To Rate';
        $order = selectOne('tbl_order', ['id' => $id]);
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