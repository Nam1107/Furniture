<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: GET,POST,PUT,PATCH,DELETE');
header('Access-Control-Allow-Headers: Content-Type,Access-Control-Allow-Headers,Authorization,X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header('HTTP/1.1 200 OK');
    exit();
}
header("Content-type: text/html; charset=utf-8");
session_start();
require './path.php';
require './configs/routes.php';
require './core/Routes.php';
require './core/Application.php';
require './database/db.php';
require './helper/middleware.php';
require './helper/validateUser.php';

$myApp = new Application();