<?php
header("Access-Control-Allow-Origin: *");
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