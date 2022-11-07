<?php

class dashboard extends Controllers
{
    public $validate_user;
    public $middle_ware;
    public $wishlist_model;
    public function __construct()
    {
        $this->wishlist_model = $this->model('dashboardModel');
        $this->middle_ware = new middleware();
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

        $report = custom("SELECT A.status,SUM(A.total) AS total,COUNT(A.ID) AS num
        FROM 
        (SELECT `order`.ID,`order`.status,`order`.createdAt,SUM(unitPrice*quantity) AS total
        FROM orderDetail,`order`
        WHERE orderID = `order`.ID
        AND `order`.createdAt > '$startDate' AND  `order`.createdAt < '$endDate'
        GROUP BY orderID) AS A
        GROUP BY A.status
        ");
        $res['status'] = 1;
        $res['report'] = $report;
        dd($res);
        exit;
    }
}