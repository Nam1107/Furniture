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

    public function addToCart()
    {
        $this->middle_ware->checkRequest('POST');
        $this->middle_ware->userOnly();
        $json = file_get_contents("php://input");
        $sent_vars = json_decode($json, TRUE);

        if (empty($sent_vars['quantity']) || empty($sent_vars['product_variation_id'])) {
            $this->loadErrors(400, 'Không đủ trường dữ liệu');
        }
        $id = $sent_vars['product_variation_id'];
        $table = 'shopping_cart';
        $userID = $_SESSION['user']['id'];
        $product = $this->cart_model->checkproduct($id, 1);
        if (!$product) {
            $this->loadErrors(404, 'Không tìm thấy sản phẩm');
        }

        $condition = [
            'user_id' => $userID,
            'product_variation_id' => $id,
        ];
        $obj = selectOne('shopping_cart', $condition);



        if (!$obj) {
            $res = $this->cart_model->getCart($userID);
            if (count($res) > 10) {
                $this->loadErrors(400, 'Giỏ hàng của bạn đã đầy');
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
            $this->loadErrors(400, 'Bạn không thể thêm quá 6 sản phẩm này vào giỏ hàng');
        }

        $quantity['quantity'] = $obj['quantity'] + $sent_vars['quantity'];
        if ($quantity['quantity'] > 6) {
            $quantity['quantity'] = 6;
        }
        update($table, ['id' => $obj['id']], $quantity);
        $res = $this->cart_model->getCart($userID);
        dd($res);
        exit();
    }

    public function removeFromCart($id = 0)
    {
        $this->middle_ware->checkRequest('DELETE');
        $this->middle_ware->userOnly();
        $userID = $_SESSION['user']['id'];
        $product = $this->cart_model->checkProduct($id, 1);
        if (!$product) {
            $this->loadErrors(404, 'Không tìm thấy sản phẩm');
        }
        $obj = $this->cart_model->getProductInCart($id);
        if (!$obj) {
            $this->loadErrors(400, 'Không tìm thấy sản phẩm trong giỏ hàng');
        }

        $table = 'shopping_cart';
        $condition = [
            'user_id' => $userID,
            'product_variation_id' => $id,
        ];
        delete($table, $condition);
        $res['msg'] = 'Thành công';
        dd($res);
        exit();
    }

    public function updateCart()
    {
        $this->middle_ware->checkRequest('PUT');
        $this->middle_ware->userOnly();

        $json = file_get_contents("php://input");
        $sent_vars = json_decode($json, TRUE);

        if (empty($sent_vars['quantity']) || empty($sent_vars['product_variation_id'])) {
            $this->loadErrors(400, 'Không đủ trường dữ liệu');
        }
        $id = $sent_vars['product_variation_id'];
        $table = 'shopping_cart';
        $product = $this->cart_model->checkProduct($id, 1);
        if (!$product) {
            $this->loadErrors(404, 'Không tìm thấy sản phẩm');
        }
        $obj = $this->cart_model->getProductInCart($id);
        if (!$obj) {
            $this->loadErrors(400, 'Không tìm thấy sản phẩm trong giỏ hàng');
        }
        if ($obj['quantity'] > 5) {
            $this->loadErrors(400, 'Bạn không thể thêm quá 6 sản phẩm này vào giỏ hàng');
        }

        $quantity = $sent_vars['quantity'];

        update($table, ['id' => $obj['id']], ['quantity' => $quantity]);
        $res['msg'] = 'Thành công';
        dd($res);
        exit();
    }

    public function incrementByOne($id = 0)
    {
        $this->middle_ware->checkRequest('PUT');
        $this->middle_ware->userOnly();
        $userID = $_SESSION['user']['ID'];
        $obj = $this->cart_model->getProductInCart($id);
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
        $obj = $this->cart_model->getProductInCart($id);
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