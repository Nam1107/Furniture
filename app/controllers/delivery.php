<?php

class delivery extends Controllers
{
    public $delivery_model;
    public $user_model;
    public function __construct()
    {
        $this->delivery_model = $this->model('deliveryModel');
        $this->user_model = $this->model('userModel');
        $this->middle_ware = new middleware();
        set_error_handler(function ($err_severity, $err_msg, $err_file, $err_line, array $err_context) {
            throw new ErrorException($err_msg, 0, $err_severity, $err_file, $err_line);
        }, E_WARNING);
    }
    function getDetail($orderID = 0)
    {
        $res = $this->delivery_model->getDetail($orderID);
        dd($res);
        exit;
    }
    function getUser($userID = 0)
    {
        $res = $this->user_model->getDetail($userID);
        dd($res);
        exit;
    }
}