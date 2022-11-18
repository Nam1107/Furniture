<?php

class report extends Controllers
{
    public $middle_ware;
    public $order_model;
    public $report_model;
    public function __construct()
    {
        $this->order_model = $this->model('orderModel');
        $this->report_model = $this->model('reportModel');

        $this->middle_ware = new middleware();
        set_error_handler(function ($severity, $message, $file, $line) {
            throw new ErrorException($message, 0, $severity, $file, $line);
        }, E_WARNING);
    }
    function getReport($report_id)
    {
        $this->middle_ware->checkRequest('GET');
        $this->middle_ware->adminOnly();
        update('order_report', ['id' => $report_id], ['checked' => 1]);
        $res = $this->report_model->getDetail($report_id, 1);

        dd($res);
        exit;
    }
    function ListReport()
    {
        $this->middle_ware->checkRequest('GET');
        $this->middle_ware->adminOnly();
        $sent_vars = $_GET;
        try {
            $page = $sent_vars['page'];
            $perPage = $sent_vars['perPage'];
        } catch (ErrorException $e) {
            $this->loadErrors(400, $e->getMessage() . " on line " . $e->getLine() . " in file " . $e->getfile());
        }
        $res = $this->report_model->getList($page, $perPage);
        dd($res);
        exit;
    }
    function deleteReport($report_id)
    {
        $this->middle_ware->checkRequest('DELETE');
        $this->middle_ware->adminOnly();
        $report = $this->report_model->getDetail($report_id, 1);
        if (!$report) {
            $this->loadErrors(404, 'Không tìm thấy bài cáo báo');
        }
        delete('order_report', ['id' => $report_id]);
        $res['status'] = 1;
        $res['msg'] = 'Thành công';
        dd($res);
        exit;
    }
    function setUnchecked($report_id)
    {
        $this->middle_ware->checkRequest('PUT');
        $this->middle_ware->adminOnly();
        $report = $this->report_model->getDetail($report_id, 1);
        if (!$report) {
            $this->loadErrors(404, 'Không tìm thấy bài cáo báo');
        }
        update('order_report', ['id' => $report_id], ['checked' => 0]);
        $res['status'] = 1;
        $res['msg'] = 'Thành công';
        dd($res);
        exit;
    }
}