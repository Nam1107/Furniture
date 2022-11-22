<?php

require_once './src/JWT.php';
require_once './src/Key.php';
require_once './src/SignatureInvalidException.php';
require_once './src/ExpiredException.php';


use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\JWT;

class middleware extends Controllers
{

    function md5Security($pwd)
    {
        return md5(md5($pwd) . MD5_PRIVATE_KEY);
    }

    function authenToken()
    {

        session_destroy();

        $headers = apache_request_headers();
        if (!isset($headers['Authorization'])) {

            $res['status'] = 0;
            $res['errors'] = 'You need a token to access';
            return $res;
        }
        $token = $headers['Authorization'];
        $check = explode(" ", $token);

        try {
            $key = base64_decode(TOKEN_SECRET);
            $jwt = JWT::decode($check[1],  new Key($key, TOKEN_ALG));
            $data = json_decode(json_encode($jwt), true);

            $name = $data['sub'];
            $role = custom("
                    SELECT `role_variation`.role_name,user.id
                    FROM `role_variation`,`user`,user_role
                    WHERE `user`.user_name = '$name'
                    AND user_role.user_id = `user`.id
                    AND `role_variation`.id = user_role.role_id
            ");

            $a = array();
            if ($role) {


                $a = array_column($role, 'role_name');

                $_SESSION['user']['role'] = $a;
                $_SESSION['user']['id'] = $role[0]['id'];
                $res['status'] = 1;
            } else {
                $res['status'] = 0;
                $res['errors'] = 'Not found user';
            }
            return $res;
        } catch (Exception $e) {
            $res['status'] = 0;
            $res['errors'] = $e->getMessage();
            return $res;
        }
    }

    function checkRequest($req)
    {
        if ($_SERVER['REQUEST_METHOD'] !== $req) {
            $this->loadErrors(400, 'Wrong method');
        }
    }

    function userOnly()
    {
        $obj = $this->authenToken();

        if ($obj['status'] == 0) {
            // dd($obj);
            $this->loadErrors(400, $obj['errors']);
            // exit();
        }
    }
    function adminOnly()
    {
        $this->userOnly();
        $role = $_SESSION['user']['role'];
        if (!in_array("ROLE_ADMIN", $role)) {
            $this->loadErrors(400, 'You are not admin');
        }
    }
    function shipperOnly()
    {
        $this->userOnly();
        $role = $_SESSION['user']['role'];
        if (!in_array("ROLE_SHIPPER", $role)) {
            $this->loadErrors(400, 'You are not admin');
        }
    }
    function guestsOnly()
    {
        if (isset($_SESSION['user'])) {
            $this->loadErrors(400, 'You have logged in');
        }
    }
}