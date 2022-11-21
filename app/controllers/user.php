<?php

class User extends Controllers
{
    public $validate_user;
    public $middle_ware;
    public $user_model;
    public function __construct()
    {
        $this->user_model = $this->model('userModel');
        $this->middle_ware = new middleware();
        set_error_handler(function ($err_severity, $err_msg, $err_file, $err_line, array $err_context) {
            throw new ErrorException($err_msg, 0, $err_severity, $err_file, $err_line);
        }, E_WARNING);
    }
    public function ListUser()
    {
        $this->middle_ware->checkRequest('GET');
        $this->middle_ware->adminOnly();
        // $json = file_get_contents("php://input");
        // $sent_vars = json_decode($json, TRUE);
        $sent_vars = $_GET;
        try {
            $page = $sent_vars['page'];
            $perPage = $sent_vars['perPage'];
            $email = $sent_vars['email'];
            $sortBy = $sent_vars['sortBy'];
            $sortType = $sent_vars['sortType'];
        } catch (Error $e) {
            $this->loadErrors(400, 'Error: input is invalid');
        }

        $res = $this->user_model->getList($page, $perPage, $email, $sortBy, $sortType);
        dd($res);
        exit();
    }

    public function getProfile()
    {
        $this->middle_ware->checkRequest('GET');
        $this->middle_ware->userOnly();
        $id = $_SESSION['user']['ID'];
        $res = $this->user_model->getDetail($id, 'id,avatar,user_name,phone');
        dd($res);
        exit();
    }

    public function getUser($id = 0)
    {
        $this->middle_ware->checkRequest('GET');
        $this->middle_ware->adminOnly();
        $res = $this->user_model->getDetail($id, 'id,avatar,user_name,phone');
        dd($res);
        exit();
    }



    function listShipper()
    {
        $this->middle_ware->checkRequest('GET');
        $this->middle_ware->adminOnly();

        $user = $this->user_model->listByRole('ROLE_SHIPPER');
        // $user = [];
        foreach ($user as $key => $each) {
            $user[$key] = $this->user_model->getDetail($each['user_id'], 'id,avatar,user_name,phone');
            unset($user[$key]['user_id']);
        }
        dd($user);
        exit;
    }
}