<?php
require_once './src/JWT.php';

use Firebase\JWT\JWT;

class Auth extends Controllers
{
    public $validate_user;
    public $middle_ware;
    public $wishlist_model;
    public function __construct()
    {
        // $this->wishlist_model = $this->model('categoryModel');
        $this->middle_ware = new middleware();
    }

    public function Logout()
    {
        $this->middle_ware->checkRequest('POST');
        session_destroy();
        $headers = apache_request_headers();
        if (!isset($headers['Authorization'])) {
            $res['status'] = 0;
            $res['error'] = 'You need a token to access';
            dd($res);
            exit();
        }
        $data = $headers['Authorization'];
        $check = explode(" ", $data);

        $token['token'] = $check[1];

        $count = delete('login_token', $token);
        // setcookie('token', '', time() - 7 * 24 * 60 * 60, '/');
        if ($count == 1) {
            $res['status'] = 1;
            $res['msg'] = 'You have successfully logout';
            dd($res);
            exit();
        } else {
            $res['status'] = 0;
            $res['errors'] = 'Refresh token has expired. You must login again';
            dd($res);
            exit();
        }
    }

    public function Login()
    {
        $this->middle_ware->checkRequest('POST');
        $this->middle_ware->guestsOnly();
        $key = 'privatekey';

        $json = file_get_contents("php://input");
        $sent_vars = json_decode($json, TRUE);

        $errors = validateLogin($sent_vars);
        $res['status'] = 0;
        if (count($errors) === 0) {

            $user = selectOne('user', ['email' => $sent_vars['email']]);

            if (!$user) {
                array_push($errors, 'Email address does not exist');
            } elseif (password_verify($sent_vars['password'], $user['password'])) {

                $id = $sessionUser['ID'] = $user['ID'];
                $email = $user['email'];
                $role = $sessionUser['role'] = $user['role'];

                $payload = [
                    'iss' => 'obrien',
                    'exp' => time() + 30 * 60,
                    'data' => [
                        'id' => $id,
                        'email' => $email,
                        'role' => $role
                    ]
                ];


                $token = JWT::encode($payload, TOKEN_SECRET, 'HS256');

                $login_token['token'] = $token;
                $login_token['userID'] = $user['ID'];
                $login_token['createdAt'] = currentTime();
                // setcookie('token', $login_token['token'], time() + 7 * 24 * 60 * 60, '/');
                create('login_token', $login_token);

                $res['status'] = 1;
                $res['token'] = $token;
                $res['refreshToken'] = $token;

                dd($res);
                exit();
            } else {
                array_push($errors, 'Wrong password');
            }
        }

        $res['errors'] = $errors;
        dd($res);
        exit();
    }
    public function refreshToken()
    {
        $this->middle_ware->checkRequest('GET');
        $headers = apache_request_headers();
        if (!isset($headers['Authorization'])) {
            $res['status'] = 0;
            $res['errors'] = 'You need a refresh token to access';
            dd($res);
            exit();
        }
        $check = $headers['Authorization'];
        $data = explode(" ", $check);
        $token = $data[1];

        $result = custom("select user.* from user, login_token where user.ID = login_token.userID and login_token.token ='$token'");

        if ($result != null && count($result) > 0) {
            $id = $result[0]['ID'];
            $email = $result[0]['email'];
            $role = $result[0]['role'];
            $payload = [
                'iss' => 'obrien',
                'exp' => time() + 30 * 60,
                'data' => [
                    'id' => $id,
                    'email' => $email,
                    'role' => $role
                ],
            ];
            $token = JWT::encode($payload, TOKEN_SECRET, 'HS256');
            $res['status'] = 1;
            $res['token'] = $token;
            dd($res);
            exit();
        } else {
            $res['status'] = 0;
            $res['error'] = 'Refresh token not invalid. You must login again';
            dd($res);
            exit();
        }
    }
    public function Register()
    {
        $this->middle_ware->checkRequest('POST');
        $this->middle_ware->guestsOnly();
        $table = 'user';

        $json = file_get_contents("php://input");
        $sent_vars = json_decode($json, TRUE);

        $errors = validateRegister($sent_vars);
        if (count($errors) === 0) {
            $sent_vars['role'] = '0';
            $sent_vars['avatar'] = 'https://staticfvvn.s3-ap-southeast-1.amazonaws.com/fv4uploads/uploads/users/4x/6gl/xtq/avatar/thumb_694526497374699.jpg';
            $sent_vars['createdAt'] = currentTime();
            $sent_vars['password'] = password_hash($sent_vars['password'], PASSWORD_DEFAULT);
            $user_id = create($table, $sent_vars);
            $res['status'] = 1;
            $res['msg'] = 'Success';
            dd($res);
            exit();
        } else {
            $this->loadErrors(400, $errors);
        }
    }
}