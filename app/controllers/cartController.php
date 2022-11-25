<?php
class cartController extends Controllers
{
    public $validate_user;
    public $middle_ware;
    public $cart_model;
    public function __construct()
    {
        $this->cart_model = $this->model('cartModel');
        $this->product_model = $this->model('productModel');
        $this->middle_ware = new middleware();
    }
    public function getCart()
    {
        $this->middle_ware->checkRequest('GET');
        $userID = 0;
        $obj = $this->middle_ware->authenToken();
        if ($obj['status'] == 1) {
            $userID = $_SESSION['user']['id'];
        }
        $res = $this->cart_model->getCart($userID);
        dd($res);
        exit();
    }

    public function addProduct($id = 0)
    {
        $this->middle_ware->checkRequest('POST');
        $this->middle_ware->userOnly();
        $json = file_get_contents("php://input");
        $sent_vars = json_decode($json, TRUE);

        if (empty($sent_vars['quantity'])) {
            $this->loadErrors(400, 'Error: quantity value invalid');
        }
        $table = 'shoppingCart';
        $userID = $_SESSION['user']['ID'];
        $product = $this->product_model->getDetail($id, 1);
        if (!$product) {
            $this->loadErrors(404, 'Not found product');
        }

        $condition = [
            'userID' => $userID,
            'productID' => $id,
        ];
        $obj = selectOne('shoppingCart', $condition);



        if (!$obj) {
            $res = $this->cart_model->getCart($userID);
            if (count($res) > 10) {
                $this->loadErrors(400, 'Your cart is full of slot');
            }

            $condition['quantity'] = $sent_vars['quantity'];
            if ($condition['quantity'] > 6) {
                $condition['quantity'] = 6;
            }
            create($table, $condition);
            $res = $this->cart_model->getCart($userID);
            dd($res);
            exit();
        }

        if ($obj['quantity'] > 5) {
            $this->loadErrors(400, 'You cannot add more than 6 quantities of this product');
        }

        $quantity['quantity'] = $obj['quantity'] + $sent_vars['quantity'];
        if ($quantity['quantity'] > 6) {
            $quantity['quantity'] = 6;
        }
        update($table, ['ID' => $obj['ID']], $quantity);
        $res = $this->cart_model->getCart($userID);
        dd($res);
        exit();
    }

    public function removeProduct($id = 0)
    {
        $this->middle_ware->checkRequest('DELETE');
        $this->middle_ware->userOnly();
        $userID = $_SESSION['user']['ID'];
        $product = $this->product_model->getDetail($id, 1);
        if (!$product) {
            $this->loadErrors(404, 'Not found product');
        }
        $obj = $this->cart_model->getProductInCart($userID, $id);
        if (!$obj) {
            $this->loadErrors(400, 'Cannot found product in your cart');
        }

        $table = 'shoppingCart';
        $condition = [
            'userID' => $userID,
            'productID' => $id,
        ];
        delete($table, $condition);
        $res = $this->cart_model->getCart($userID);
        dd($res);
        exit();
    }

    public function incrementByOne($id = 0)
    {
        $this->middle_ware->checkRequest('PUT');
        $this->middle_ware->userOnly();
        $userID = $_SESSION['user']['ID'];
        $obj = $this->cart_model->getProductInCart($userID, $id);
        if (!$obj) {
            $this->loadErrors(400, 'Cannot found product in your cart');
        }
        if ($obj['quantity'] > 5) {
            $this->loadErrors(400, 'You cannot add more than 6 quantities of this product');
        }
        custom("
        UPDATE shoppingCart SET quantity = if(quantity < 6,quantity + 1, 6) WHERE userID = $userID AND productID = $id
        ");
        $res = $this->cart_model->getCart($userID);
        dd($res);
        exit();
    }

    public function decrementByOne($id = 0)
    {
        $this->middle_ware->checkRequest('PUT');
        $this->middle_ware->userOnly();
        $table = 'shoppingCart';
        $userID = $_SESSION['user']['ID'];
        $obj = $this->cart_model->getProductInCart($userID, $id);
        if (!$obj) {
            $this->loadErrors(400, 'Cannot found product in your cart');
        }

        custom("
        UPDATE shoppingCart SET quantity = if(quantity > 1 ,quantity - 1, 1) WHERE userID = $userID AND productID = $id
        ");
        $res = $this->cart_model->getCart($userID);
        dd($res);
        exit();
    }
}