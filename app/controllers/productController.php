<?php

class ProductController extends Controllers
{
    public $product_model;
    public $middle_ware;
    public function __construct()
    {

        $this->middle_ware = new middleware();
        $this->product_model = $this->model('productModel');
        set_error_handler(function ($severity, $message, $file, $line) {
            throw new ErrorException($message, 0, $severity, $file, $line);
        }, E_WARNING);
    }

    public function ListProduct()
    {
        $this->middle_ware->checkRequest('GET');
        $sent_vars = $_GET;

        try {
            $page = $sent_vars['page'];
            $perPage = $sent_vars['perPage'];
            $category = $sent_vars['category'];
            $sale = $sent_vars['sale'];
            $sortBy = $sent_vars['sortBy'];
            $sortType = $sent_vars['sortType'];
            $name = $sent_vars['name'];
        } catch (Error $e) {
            $this->loadErrors(400, 'Error: input is invalid');
        }

        $IsPublic = 1;

        if ($name == 'price') $name = 'curPrice';

        $res = $this->product_model->getList($page, $perPage, $name, $category, $IsPublic, $sale, $sortBy,  $sortType);

        dd($res);
        exit();
    }
    public function AdminListProduct()
    {
        $this->middle_ware->checkRequest('GET');
        $this->middle_ware->adminOnly();
        $sent_vars = $_GET;


        try {
            $page = $sent_vars['page'];
            $perPage = $sent_vars['perPage'];
            $category = $sent_vars['category'];
            $sale = $sent_vars['sale'];
            $sortBy = $sent_vars['sortBy'];
            $sortType = $sent_vars['sortType'];
            $name = $sent_vars['name'];
            if ($name == 'price') $name = 'curPrice';
            $IsPublic = '';
        } catch (Error) {
            $this->loadErrors(400, 'Error: input is invalid');
        }

        $res = $this->product_model->getList($page, $perPage, $name, $category, $IsPublic, $sale, $sortBy,  $sortType);

        dd($res);
        exit();
    }
    public function getProduct($id = 0)
    {
        $this->middle_ware->checkRequest('GET');
        $res = $this->product_model->getDetail($id, '1');
        dd($res);
        exit();
    }
    public function AdminGetProduct($id = 0)
    {
        $this->middle_ware->checkRequest('GET');
        $this->middle_ware->adminOnly();
        $res = $this->product_model->getDetail($id, '');
        dd($res);
        exit();
    }
}