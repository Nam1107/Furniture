<?php

class Gallery extends Controllers
{
    public $validate_user;
    public $middle_ware;
    public $wishlist_model;
    public function __construct()
    {
        $this->wishlist_model = $this->model('galleryModel');
        $this->middle_ware = new middleware();
    }
    public function addImage($id)
    {
        $this->middle_ware->checkRequest('POST');
        $this->middle_ware->adminOnly();
        $table = 'gallery';

        $json = file_get_contents("php://input");
        $sent_vars = json_decode($json, TRUE);
        try {
            $urls = $sent_vars['gallery'];
        } catch (Error $e) {
            $this->loadErrors(400, 'Error: input is invalid');
        }
        foreach ($urls as $key => $url) :
            $obj['productID'] = $id;
            $obj['URLImage'] =  $url;
            $obj['Sort'] =  $key;
            create($table, $obj);
        endforeach;
        update('product', ['ID' => $id], ['updatedAt' => currentTime()]);
        $res['status'] = 1;
        $res['msg'] = 'Success';
        dd($res);
        exit();
    }

    public function deleteImage($id)
    {
        $this->middle_ware->checkRequest('DELETE');
        $table = 'gallery';
        $this->middle_ware->adminOnly();
        $image['ID'] = $id;
        $product = selectOne($table, $image);
        if ($product) {
            delete($table, $image);
            $productID = $product['productID'];
            update('product', ['ID' => $product['productID']], ['updatedAt' => currentTime()]);
            $res['status'] = 1;
            $res['msg'] = 'Success';
            $res['obj'] = custom("
            SELECT * from gallery where productID = $productID
            ");
            dd($res);
            exit();
        }
        $res['status'] = 0;
        $res['errors'] = 'Not found image by ID';
        dd($res);
        exit();
    }
    public function updateGallery($id)
    {
        $this->middle_ware->checkRequest('PUT');
        $table = 'gallery';
        $this->middle_ware->adminOnly();

        $json = file_get_contents("php://input");
        $sent_vars = json_decode($json, TRUE);

        $image['ID'] =  $id;
        update($table, $image, $sent_vars);
        $product = selectOne($table, $image);
        $productID = $product['productID'];
        update('product', ['ID' => $product['productID']], ['updatedAt' => currentTime()]);
        $res['obj'] = custom("
            SELECT * from gallery where productID = $productID
            ");
        $res['status'] = 1;
        $res['msg'] = 'Success';
        dd($res);
        exit();
    }
}