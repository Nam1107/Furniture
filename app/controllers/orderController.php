<?php

class orderController extends Controllers
{
    public $middle_ware;
    public $order_model;
    public $delivery_model;
    public $user_model;
    public $shipping_model;
    public $cart_model;

    public $render_view;
    public function __construct()
    {
        $this->order_model = $this->model('orderModel');
        $this->delivery_model = $this->model('deliveryModel');
        $this->shipping_model = $this->model('shippingModel');
        $this->user_model = $this->model('userModel');
        $this->render_view = $this->render('renderView');
        $this->cart_model = $this->model('cartModel');
        $this->middle_ware = new middleware();
        set_error_handler(function ($severity, $message, $file, $line) {
            throw new ErrorException($message, 0, $severity, $file, $line);
        }, E_WARNING);
    }

    public function createNewOrder($user_id, $note,  $payment_type, $address)
    {
        $order['user_id'] = $user_id;
        $order['note'] = $note;
        $order['status'] = status_order[0];
        $order['address'] = $address;
        $order['payment_type'] = $payment_type;
        $order['created_date'] = currentTime();

        $order_id = create('tbl_order', $order);
        return $order_id;
    }
    public function createOrderDetail($order_id, $product_variation_id, $unit_price, $quantity)
    {
        $condition = [
            "order_id" => $order_id,
            "product_variation_id" => $product_variation_id,
            "unit_price" => $unit_price,
            "quantity" => $quantity
        ];
        create('order_detail', $condition);
    }
    public function createOrder()
    {
        # code...
        $this->middle_ware->checkRequest('POST');
        $this->middle_ware->userOnly();

        $user_id = $_SESSION['user']['id'];
        $json = file_get_contents("php://input");
        $sent_vars = json_decode($json, TRUE);
        try {
            $cart = $this->cart_model->getCart($user_id)['obj'];
            if (!$cart) {
                $this->loadErrors(400, 'Gi??? h??ng c???a ban b??? tr???ng');
            }
            foreach ($cart as $key => $val) {
                if ($val['canBuy'] == 0) {
                    $this->loadErrors(400, 'M???t s??? s???n ph???m trong gi??? h??ng ???? h???t');
                }
            }


            $note = $sent_vars['note'];
            $payment_type = $sent_vars['payment_type'];
            $address = $sent_vars['address'];


            $order_id = $this->createNewOrder($user_id, $note,  $payment_type, $address);



            #update sold of product
            foreach ($cart as $key => $val) {
                $quantity = $val['quantity'];
                $product_variation_id = $val['product_variation_id'];
                $product_id = $val['product_id'];
                custom("
            UPDATE product_variation SET stock = if(stock < $quantity,0, stock - $quantity), sold = if(sold IS NULL, $quantity , sold + $quantity) WHERE id = $product_variation_id;
            UPDATE product SET sold = if(sold IS NULL, $quantity , sold + $quantity) WHERE id = $product_id;
            ");
                $this->createOrderDetail($order_id, $val['product_variation_id'], $val['price'], $val["quantity"]);
            }
            delete('shopping_cart', ['user_id' => $user_id]);
            $this->shipping_model->create($order_id, $user_id, shipping_status[0]);

            $res['order_id'] = $order_id;
            dd($res);
            exit();
        } catch (ErrorException $e) {
            $this->render_view->loadErrors(400, $e->getMessage() . " on line " . $e->getLine() . " in file " . $e->getfile());
        }
    }

    function listStatus()
    {
        $this->middle_ware->checkRequest('GET');
        $this->render_view->ToView(status_order);
        exit();
    }

    function orderFail()
    {
        $this->middle_ware->checkRequest('GET');
        $this->render_view->ToView(order_fail);
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
            $this->render_view->loadErrors(404, "Kh??ng t??m th???y ????n h??ng");
        }
        if ($order['status'] != status_order[0]) {
            $this->render_view->loadErrors(404, "????n h??ng kh??ng trong tr???ng th??i " . status_order[0]);
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
            $this->render_view->loadErrors(400, $e->getMessage() . " on line " . $e->getLine() . " in file " . $e->getfile());
        }
        $res['msg'] = 'Th??nh c??ng';
        $this->render_view->ToView($res);
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
            $this->render_view->loadErrors(400, $e->getMessage() . " on line " . $e->getLine() . " in file " . $e->getfile());
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
        $this->render_view->ToView($res);

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
            $this->render_view->loadErrors(400, $e->getMessage() . " on line " . $e->getLine() . " in file " . $e->getfile());
        }

        $res = $this->order_model->listOrder($status, $page, $perPage, $startDate, $endDate);
        foreach ($res['obj'] as $key => $each) {
            $user_id = empty($each['user_id']) ? 0 : $each['user_id'];
            $res['obj'][$key]['customer'] = $this->user_model->getDetail($user_id, 'id,avatar,user_name,phone,email', 0);
            unset($res['obj'][$key]['user_id']);
        }
        $this->render_view->ToView($res);
        exit();
    }


    public function getOrder($order_id = 0)
    {
        $this->middle_ware->checkRequest('GET');
        $this->middle_ware->adminOnly();
        $res = $this->order_model->getDetail($order_id, '*', 1);

        if (!$res) {
            $this->render_view->loadErrors(404, 'Kh??ng t??m th???y ????n h??ng');
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

        $this->render_view->ToView($res);
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
            $this->render_view->loadErrors(404, 'Kh??ng t??m th???y ????n h??ng');
        }

        try {
            if ($order['status'] != status_order[0]) {
                $this->render_view->loadErrors(404, 'Tr???ng th??i ????n h??ng kh??ng h???p l???');
            }
            $description = $sent_vars['description'];
            if (!in_array($description, order_fail)) {
                $this->render_view->loadErrors(400, 'L?? do h???y kh??ng h???p l???');
            }
            $user_id = $_SESSION['user']['id'];
            $description = "Admin h???y ????n h??ng v?? l?? do: " . $description;

            $this->shipping_model->create($order_id, $user_id, $description);
            $this->order_model->updateStatus($order_id, status_order[5]);
        } catch (ErrorException $e) {
            $this->render_view->loadErrors(400, $e->getMessage() . " on line " . $e->getLine() . " in file " . $e->getfile());
        }
        $res['msg'] = 'Th??nh c??ng';
        $this->render_view->ToView($res);
        exit;
    }
}