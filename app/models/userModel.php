<?php

class userModel
{
    protected $table = 'user';
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


        $res['total_count'] = $totalCount[0]['total_count'];
        $res['numOfPage'] = ceil($check);
        $res['page'] = $page;
        $res['obj'] = $obj;

        return ($res);
    }

    function listByRole($value)
    {
        $users = custom("SELECT `user_role`.user_id 
        FROM `user_role`, `role_variation`,
        (SELECT user_id, COUNT(user_id) AS num
        FROM user_role
        GROUP BY user_id) AS A
        WHERE role_variation.role_name = '$value' 
        AND user_role.role_id = role_variation.id
        AND A.num = 2
        AND user_role.user_id = A.user_id");

        return $users;
    }

    public function getDetail($id, $value = '*', $all = 0)
    {
        $obj = custom("SELECT $value
        FROM `user`
        WHERE user.id = $id");
        if (!$obj) return null;
        $obj = $obj[0];
        if ($all == 1) {
            $role = custom("SELECT role_variation.role_name
        FROM role_variation,user_role
        WHERE user_role.user_id = $id
        AND role_variation.id= user_role.role_id");

            $a = array();

            foreach ($role as $key => $each) {
                array_push($a, $each['role_name']);
            }

            $obj['role'] = $a;
        }


        return $obj;
    }
}