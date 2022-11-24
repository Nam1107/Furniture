<?php

global $conn;
try {
   $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,  DB_USER, DB_PASS);
   // set the PDO error mode to exception
   $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {

   http_response_code(400);
   $res['status'] = 0;
   $res['errors'] = $e->getMessage();
   echo json_encode($res);
   exit();
}

// $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_TABLE);
// mysqli_set_charset($conn, 'UTF8');
// // Check connection
// if ($conn->connect_error) {
//    die("Connection failed: " . $conn->connect_error);
// }