<?php

class review extends Controllers
{
    public $validate_user;
    public $middle_ware;
    public $review_model;
    public function __construct()
    {
        $this->review_model = $this->model('reviewModel');
        $this->middle_ware = new middleware();
        set_error_handler(function ($err_severity, $err_msg, $err_file, $err_line, array $err_context) {
            throw new ErrorException($err_msg, 0, $err_severity, $err_file, $err_line);
        }, E_WARNING);
    }
    public function addReview($id)
    {
        $this->middle_ware->checkRequest('POST');
        $this->middle_ware->userOnly();

        $order = selectOne('order', ['ID' => $id]);
        if (!$order) {
            $this->loadErrors(404, 'No orders yet');
        } elseif ($order['status'] != 'To Rate') {
            $this->loadErrors(400, 'Rating is not available');
        }

        update('order', ['ID' => $id], ['status' => 'Completed']);

        $json = file_get_contents("php://input");
        $sent_vars = json_decode($json, TRUE);
        $userID = $_SESSION['user']['ID'];
        try {
            foreach ($sent_vars['review'] as $key => $val) {
                $proID = $val['productID'];
                $rate = $val['rate'];
                $this->review_model->create($proID, $userID, $rate, $val['comment']);
                custom("
            UPDATE product SET rate = IF(rate = 0,$rate,(rate + $rate)/2), numOfReviews = (numOfReviews + 1) WHERE ID = $proID
            ");
            }
        } catch (Error $e) {
            $this->loadErrors(400, 'Error: input is invalid');
        }
        $res['status'] = 1;
        $res['msg'] = "Success";
        dd($res);
        exit();
    }

    public function productReview($id = 0)
    {
        $this->middle_ware->checkRequest('GET');
        $json = file_get_contents("php://input");
        $sent_vars = json_decode($json, TRUE);
        try {
            $rate = $sent_vars['rate'];
            $page = $sent_vars['page'];
            $perPage = $sent_vars['perPage'];
        } catch (Error $e) {
            $this->loadErrors(400, 'Error: input is invalid');
        }
        $res = $this->review_model->listByProduct($id, $page, $perPage, $rate, 1);
        dd($res);
        exit();
    }

    public function adminProductReview($id = 0)
    {
        $this->middle_ware->checkRequest('GET');
        $this->middle_ware->adminOnly();
        $json = file_get_contents("php://input");
        $sent_vars = json_decode($json, TRUE);


        try {
            $rate = $sent_vars['rate'];
            $page =  $sent_vars['page'];
            $perPage = $sent_vars['perPage'];
        } catch (Error $e) {
            $this->loadErrors(400, 'Error: input is invalid');
        }
        $res = $this->review_model->listByProduct($id, $page, $perPage, $rate, '');
        dd($res);
        exit();
    }

    public function adminSetPublic($id = 0)
    {
        $this->middle_ware->checkRequest('PUT');
        $this->middle_ware->adminOnly();

        $obj = $this->review_model->getDetail($id);
        $IsPublic = !$obj['IsPublic'];
        update('review', ['ID' => $id], ['IsPublic' => $IsPublic]);
        $res['status'] = 1;
        $res['msg'] = 'Success';
        dd($res);
        exit();
    }

    public function myReview()
    {
        $this->middle_ware->checkRequest('GET');
        $this->middle_ware->userOnly();
        $userID = $_SESSION['user']['ID'];

        $json = file_get_contents("php://input");
        $sent_vars = json_decode($json, TRUE);
        try {
            $page = $sent_vars['page'];
            $perPage = $sent_vars['perPage'];
        } catch (Error $e) {
            $this->loadErrors(400, 'Error: input is invalid');
        }

        $res = $this->review_model->listByUser($userID, $page, $perPage, 1);

        dd($res);
    }
}