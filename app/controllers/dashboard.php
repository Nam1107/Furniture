<?php

class dashboard extends Controllers
{
    public $middle_ware;
    public $wishlist_model;
    public function __construct()
    {
        $this->wishlist_model = $this->model('dashboardModel');
        $this->middle_ware = new middleware();
        set_error_handler(function ($err_severity, $err_msg, $err_file, $err_line, array $err_context) {
            throw new ErrorException($err_msg, 0, $err_severity, $err_file, $err_line);
        }, E_WARNING);
    }
    public function report()
    {
        $this->middle_ware->checkRequest('GET');
        $this->middle_ware->adminOnly();

        $json = file_get_contents("php://input");
        $sent_vars = json_decode($json, TRUE);


        try {
            $startDate = $sent_vars['startDate'];
            $endDate = $sent_vars['endDate'];
        } catch (Error $e) {
            $this->loadErrors(400, 'Error: input is invalid');
        }

        $report = custom("SELECT A.status,SUM(A.total) AS total,COUNT(A.id) AS num
        FROM 
        (SELECT `order`.id,`order`.status,`order`.created_date,SUM(unit_price*quantity) AS total
        FROM order_detail,`order`
        WHERE order_id = `order`.id
        AND `order`.created_date > '$startDate' AND  `order`.created_date < '$endDate'
        GROUP BY order_id) AS A
        GROUP BY A.status
        ");
        $res['report'] = $report;
        dd($res);
        exit;
    }
}