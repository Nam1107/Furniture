<?php

class Product extends Controllers
{
    public $model_product;
    public $middle_ware;
    public function __construct()
    {

        $this->middle_ware = new middleware();
        $this->model_product = $this->model('productModel');
        set_error_handler(function ($err_severity, $err_msg, $err_file, $err_line, array $err_context) {
            throw new ErrorException($err_msg, 0, $err_severity, $err_file, $err_line);
        }, E_WARNING);
    }

    public function ListProduct()
    {
        $this->middle_ware->checkRequest('GET');
        $res['status'] = 1;
        // $json = file_get_contents("php://input");
        // $sent_vars = json_decode($json, TRUE);

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

        $res = $this->model_product->getList($page, $perPage, $name, $category, $IsPublic, $sale, $sortBy,  $sortType);

        dd($res);
        exit();
    }
    public function AdminListProduct()
    {
        $this->middle_ware->checkRequest('GET');
        $this->middle_ware->adminOnly();
        $res['status'] = 1;
        // $json = file_get_contents("php://input");
        // $sent_vars = json_decode($json, TRUE);

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

        $res = $this->model_product->getList($page, $perPage, $name, $category, $IsPublic, $sale, $sortBy,  $sortType);

        dd($res);
        exit();
    }
    public function getProduct($id = 0)
    {
        $this->middle_ware->checkRequest('GET');
        $res = $this->model_product->getDetail($id, '1');
        dd($res);
        exit();
    }
    public function AdminGetProduct($id = 0)
    {
        $this->middle_ware->checkRequest('GET');
        $this->middle_ware->adminOnly();
        $res = $this->model_product->getDetail($id, '');
        dd($res);
        exit();
    }

    public function createProduct()
    {
        $this->middle_ware->checkRequest('POST');
        $this->middle_ware->adminOnly();
        $json = file_get_contents("php://input");
        $sent_vars = json_decode($json, TRUE);

        try {
            $cate = $sent_vars['category'];
            $gallery = $sent_vars['gallery'];
            $category = custom("
                SELECT * FROM category WHERE name LIKE '%$cate%' 
            ");

            if (empty($category)) {
                throw new Error();
            }

            $product = [
                'categoryID' => $category[0]['ID'],
                'name' =>  $sent_vars['name'],
                'price' => $sent_vars['price'],
                'image' => $sent_vars['image'],
                'description' => $sent_vars['description'],
                'stock' => $sent_vars['stock'],
                'IsPublic' => $sent_vars['IsPublic'],
            ];
        } catch (Error) {
            $this->loadErrors(400, 'Error: input is invalid');
        }

        $res = $this->model_product->create($product, $gallery);
        dd($res);
        exit();
    }

    public function deleteProduct($id = 0)
    {
        $this->middle_ware->checkRequest('DELETE');
        $this->middle_ware->adminOnly();
        $res = $this->model_product->delete($id);
        dd($res);
        exit();
    }
    public function updateProduct($id = 0)
    {
        $this->middle_ware->checkRequest('PUT');
        $this->middle_ware->adminOnly();
        $json = file_get_contents("php://input");
        $sent_vars = json_decode($json, TRUE);
        $res = $this->model_product->update($id, $sent_vars);
        dd($res);
        exit();
    }
}