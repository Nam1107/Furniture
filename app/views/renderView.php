<?php
class renderView
{
    function ToView($value)
    {
        echo json_encode($value);
        exit();
    }
    function loadErrors($code, $errors)
    {
        http_response_code($code);
        $res['status'] = 0;
        $res['errors'] = $errors;
        echo $res;
        exit();
    }
}