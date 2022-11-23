<?php
require_once './src/JWT.php';

use Firebase\JWT\JWT;

class AuthController extends Controllers
{
    public $middle_ware;
    public $render_view;
    public function __construct()
    {
        $this->render_view = $this->render('renderView');
        // $this->render_view->loadErrors
        // $this->render_view->ToView
        $this->middle_ware = new middleware();
    }

    public function Logout()
    {
        $this->middle_ware->checkRequest('POST');
        session_destroy();
        $headers = apache_request_headers();
        if (!isset($headers['Authorization'])) {
            $this->render_view->loadErrors(400, 'You need a token to access');
        }
        $data = $headers['Authorization'];
        $check = explode(" ", $data);

        $token['token'] = $check[1];
        $res['msg'] = 'You have successfully logout';
        $this->render_view->ToView($res);
        exit();
    }

    public function Login()
    {
        $this->middle_ware->checkRequest('POST');
        $this->middle_ware->guestsOnly();

        $json = file_get_contents("php://input");
        $sent_vars = json_decode($json, TRUE);

        $errors = validateLogin($sent_vars);
        if (count($errors) === 0) {

            $user = selectOne('user', ['email' => $sent_vars['email']]);

            if (!$user) {
                array_push($errors, 'Email address does not exist');
            } elseif (password_verify($sent_vars['password'], $user['password'])) {

                $payload = [
                    'sub' => $user['user_name'],
                    'iat' => time(),
                    'exp' => time() + 1000000000,

                ];
                $key = base64_decode(TOKEN_SECRET);
                $token = JWT::encode($payload, $key, TOKEN_ALG);
                $res['token'] = $token;

                $this->render_view->ToView($res);
                exit();
            } else {
                array_push($errors, 'Wrong password');
            }
        }
        $this->render_view->loadErrors(400, $errors);
    }
}