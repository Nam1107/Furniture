<?php

class userModel
{
    protected $table = 'user';
    public $middle;
    public function __construct()
    {
        $this->middle = new middleware();
    }

    public function getList($page, $perPage, $email, $sortBy, $sortType)
    {
        $offset = $perPage * ($page - 1);
        $total = custom("
        SELECT COUNT(ID) as total
        FROM (SELECT * FROM `user` WHERE email LIKE '%$email%' ORDER BY $sortBy $sortType) as B
        ");
        $check = ceil($total[0]['total'] / $perPage);

        $obj = custom("
        SELECT * FROM `user` WHERE email LIKE '%$email%' ORDER BY $sortBy $sortType LIMIT $perPage OFFSET $offset
        ");
        $totalCount = custom("SELECT COUNT(*)  AS totalCount FROM  `user`");

        $res['status'] = 1;
        $res['totalCount'] = $totalCount[0]['totalCount'];
        $res['numOfPage'] = ceil($check);
        $res['page'] = $page;
        $res['obj'] = $obj;

        return ($res);
    }

    public function getDetail($id)
    {
        $obj = selectOne('user', ['ID' => $id]);
        if (!$obj) {
            http_response_code(404);
            $res['status'] = 0;
            $res['errors'] = 'Not found user by ID';
            return $res;
        }
        $res['status'] = 1;
        $res['msg'] = 'Success';
        $res['obj'] = $obj;
        return $res;
    }

    public function delete($id)
    {
        $obj = selectOne('`user`', ['ID' => $id]);
        if (!$obj) {
            http_response_code(404);
            $res['status'] = 0;
            $res['errors'] = 'Not found user by ID';
            return $res;
        }
        $userID['ID'] = $id;
        delete('user', $userID);
        $res['status'] = 1;
        $res['msg'] = 'Success';
        return $res;
    }
    public function update($id, $sent_vars)
    {
        update('user', ['ID' => $id], $sent_vars);
        $res['status'] = 1;
        $res['msg'] = 'Success';
        return $res;
    }

    public function changePass($id, $var)
    {
        update('user', ['ID' => $id], $var);
        $res['status'] = 1;
        $res['msg'] = 'Success';
        return $res;
    }
}