<?php
define('ROOT_PATH',         realpath(dirname(__FILE__)));
// define('BASE_URL',          'http://localhost/obrien');
define('BASE_URL',          'https://furniture2022.herokuapp.com/');


// define('DB_HOST',           'localhost');
// define('DB_USER',           'root');
// define('DB_PASS',           '');
// define('DB_NAME',           '');
// define('DB_TABLE',          'obrien');


// Host: sql6.freesqldatabase.com
// Database name: sql6525734
// Database user: sql6525734
// Database password: bs4SfUqIYX
// Port number: 3306

// Host: sql12.freesqldatabase.com
// Database name: sql12540408
// Database user: sql12540408
// Database password: fefDVcK6P1
// Port number: 3306

// define('DB_HOST',           'sql12.freesqldatabase.com');
// define('DB_USER',           'sql12540408');
// define('DB_PASS',           'fefDVcK6P1');
// define('DB_NAME',           'sql12540408');

define('DB_HOST',           'us-cdbr-east-06.cleardb.net');
define('DB_USER',           'bf916f753fdc6a');
define('DB_PASS',           '7fc811e0');
define('DB_NAME',           'heroku_23ab225e23b1805');

define('DB_CHARSET',          'utf8');
// define('PASSWORD_KEY',          'obrien');

define('TOKEN_ALG',          'HS512');
define('TOKEN_SECRET',          'furnitureShop');
define('MD5_PRIVATE_KEY',   '2342kuhskdfsd23(&kusdhfjsgJYGJGsfdf384');

const status_order = [
    0 => 'Chờ vận chuyển',
    1 => 'Chờ nhận hàng',
    2 => 'Chờ xác nhận',
    3 => 'Chờ đánh giá',
    4 => 'Hoàn thành',
    5 => 'Hủy'
];

const shipping_status = [
    0 => 'Đơn hàng đã được đặt',
    1 => 'Đơn hàng đang được vận chuyển',
    2 => 'Đơn hàng đã được giao',
    3 => 'Người dùng xác nhận: đã nhận được hàng'
];

const shipping_fail = [
    0 => 'Khách hàng không nghe máy',
    1 => 'Khách hàng hẹn lấy hàng vào ngày khác'
];

const delivery_status = [
    0 => 'Đang xử lý',
    1 => 'Thành công',
    2 => 'Thất bại'
];