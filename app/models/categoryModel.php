<?php

class categoryModel
{
    function getList()
    {
        $res['status'] = 1;
        $res['msg'] = 'Success';
        $obj = custom("
            SELECT * from category
        ");
        $res['obj'] = $obj;
        return ($res);
    }
    function create($name, $desc)
    {

        $condition = [
            'name' => $name,
            'description' => $desc,
        ];
        $res['status'] = 1;
        $res['msg'] = 'Success';
        create('category', $condition);
        return ($res);
    }
}