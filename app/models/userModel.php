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
        $totalCount = custom("SELECT COUNT(*)  AS total_count FROM  `user`");

        $res['status'] = 1;
        $res['total_count'] = $totalCount[0]['total_count'];
        $res['numOfPage'] = ceil($check);
        $res['page'] = $page;
        $res['obj'] = $obj;

        return ($res);
    }

    public function getDetail($id)
    {
        $obj = custom("SELECT `user`.id,`user`.user_name,`user`.avatar,`user`.phone
        FROM `user`
        WHERE user.id = $id")[0];



        $role = custom("SELECT role.role_name
        FROM role,user_role
        WHERE user_role.user_id = $id
        AND role.id= user_role.role_id");

        $a = array();

        foreach ($role as $key => $each) {
            array_push($a, $each['role_name']);
        }

        $obj['role'] = $a;

        return $obj;
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