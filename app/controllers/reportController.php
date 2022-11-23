<?php

class reportController extends Controllers
{
    public $middle_ware;
    public $order_model;
    public $report_model;
    public $render_view;
    public function __construct()
    {
        $this->order_model = $this->model('orderModel');
        $this->report_model = $this->model('reportModel');
        $this->render_view = $this->render('renderView');

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

        $this->render_view->ToView($res);
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
            $this->render_view->loadErrors(400, $e->getMessage() . " on line " . $e->getLine() . " in file " . $e->getfile());
        }
        $res = $this->report_model->getList($page, $perPage);
        $this->render_view->ToView($res);
        exit;
    }
    function deleteReport($report_id)
    {
        $this->middle_ware->checkRequest('DELETE');
        $this->middle_ware->adminOnly();
        $report = $this->report_model->getDetail($report_id, 1);
        if (!$report) {
            $this->render_view->loadErrors(404, 'Không tìm thấy bài cáo báo');
        }
        delete('order_report', ['id' => $report_id]);
        $res['msg'] = 'Thành công';
        $this->render_view->ToView($res);
        exit;
    }
    function setUnchecked($report_id)
    {
        $this->middle_ware->checkRequest('PUT');
        $this->middle_ware->adminOnly();
        $report = $this->report_model->getDetail($report_id, 1);
        if (!$report) {
            $this->render_view->loadErrors(404, 'Không tìm thấy bài cáo báo');
        }
        update('order_report', ['id' => $report_id], ['checked' => 0]);
        $res['msg'] = 'Thành công';
        $this->render_view->ToView($res);
        exit;
    }
}