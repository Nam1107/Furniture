<?php

require_once './src/JWT.php';
require_once './src/Key.php';
require_once './src/SignatureInvalidException.php';
require_once './src/ExpiredException.php';


use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;

class middleware extends Controllers
{

    function md5Security($pwd)
    {
        return md5(md5($pwd) . MD5_PRIVATE_KEY);
    }

    function authenToken()
    {
        $headers = apache_request_headers();
        if (!isset($headers['Authorization'])) {
            session_destroy();
            $res['status'] = 0;
            $res['errors'] = 'You need a token to access';
            return $res;
            // $this->loadErrors(400, 'You need a token to access');
        }
        $token = $headers['Authorization'];
        $check = explode(" ", $token);
        try {
            $jwt = JWT::decode($check[1],  new Key(TOKEN_SECRET, 'HS256'));
            $data = json_decode(json_encode($jwt), true);
            $id = $data['data']['id'];
            $obj = custom("SELECT user.ID,user.role FROM user where ID = $id");
            $_SESSION['user'] = $obj[0];
            $res['status'] = 1;
            $res['obj'] = $obj[0];
            return $res;
        } catch (ExpiredException) {
            session_destroy();
            $res['status'] = 0;
            $res['errors'] = 'Expired token';
            return $res;
            // $this->loadErrors(400, 'Expired token');
        } catch (Exception) {
            session_destroy();
            $res['status'] = 0;
            $res['errors'] = 'Token verification failed';
            return $res;
            // $this->loadErrors(400, 'Token verification failed');
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
        if ($_SESSION['user']['role'] != 1) {
            $this->loadErrors(400, 'You are not admin');
        }
    }
    function shipperOnly()
    {
        $this->userOnly();
        if ($_SESSION['user']['role'] != 2) {
            $this->loadErrors(400, 'You are not shipper');
        }
    }
    function guestsOnly()
    {
        if (isset($_SESSION['userID'])) {
            $this->loadErrors(400, 'You have logged in');
        }
    }
}