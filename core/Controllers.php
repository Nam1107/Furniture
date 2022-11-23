<?php
class Controllers
{
    public function model($model)
    {
        if (file_exists("./app/models/" . $model . ".php")) {
            require_once "./app/models/" . $model . ".php";
            if (class_exists($model)) {
                $model = new $model();
                return $model;
            }
        }
        return false;
    }
    public function render($view)
    {
        if (file_exists("./app/views/" . $view . ".php")) {
            require_once "./app/views/" . $view . ".php";
            if (class_exists($view)) {
                $view = new $view();
                return $view;
            }
        }
        return false;
    }
    public function loadErrors($code, $errors)
    {
        http_response_code($code);
        $res['status'] = 0;
        $res['errors'] = $errors;
        dd($res);
        exit();
    }

    function find(mixed $value, array $array = [])
    {
        $res = null;
        foreach ($array as $key => $a) {
            if ($array[$key] == $value) {
                $res = $key;
                break;
            }
        }
        return $res;
    }
}