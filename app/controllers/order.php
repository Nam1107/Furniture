<?php

class order extends Controllers
{
    public $middle_ware;
    public $order_model;
    public $delivery_model;
    public $user_model;
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
            $input = [
                "note" => $note,
                "address" => $address
            ];
            update('tbl_order', ['id' => $order_id], $input);
        } catch (ErrorException $e) {
            $this->loadErrors(400, $e->getMessage() . " on line " . $e->getLine() . " in file " . $e->getfile());
        }
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
            $user_id = $_SESSION['user']['id'];
            $description = "Admin hủy đơn hàng vì lý do: " . $description;
            $shipping = [
                "order_id" => $order_id,
                "description" => $description,
                "created_date" => currentTime(),
                "created_by" => $user_id
            ];

            create('shipping_report', $shipping);
            update('tbl_order', ['id' => $order_id], ['status' => status_order[5]]);
        } catch (ErrorException $e) {
            $this->loadErrors(400, $e->getMessage() . " on line " . $e->getLine() . " in file " . $e->getfile());
        }
        $res['msg'] = 'Thành công';
        dd($res);
        exit;
    }
}