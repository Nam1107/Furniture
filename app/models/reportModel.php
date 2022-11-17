<?php

class reportModel
{
    function getDetail($report_id, $all = 0)
    {
        $report = custom("
        SELECT *
        FROM order_report
        WHERE id = $report_id
        ");
        if (!$report) {
            return null;
        } else {
            $report = $report[0];
        }
        $user_id = $report['user_id'];
        $user = custom("SELECT id,avatar,user_name,phone,email
        FROM `user`
        WHERE user.id = $user_id");
        // if (!$user) return null;
        if (!$user) {
            $user = null;
        } else {
            $user = $user[0];
        }
        unset($report['user_id']);
        $report['user'] = $user;
        if ($all == 1) {
            $product_variation_id = $report['product_variation_id'];
            $product = custom("SELECT product.id, product_variation.image,product.name,product_variation.color,product_variation.size
            FROM `product`,product_variation	
            WHERE `product_variation`.id = $product_variation_id
            And product.id = `product_variation`.product_id
            ");
            if (!$product) {
                $product = null;
            } else {
                $product = $product[0];
            }
            $report['product'] = $product;
        }


        return $report;
    }
    function getList($page, $perPage)
    {
        $offset = $perPage * ($page - 1);
        $total = custom(
            "SELECT COUNT(id) as total
            FROM (
                SELECT id
                FROM order_report
            ) AS B
        "
        );
        $unchecked = custom(
            "SELECT COUNT(id) as total
            FROM (
                SELECT id
                FROM order_report
                WHERE checked = 0
            ) AS B
        "
        );

        $check = ceil($total[0]['total'] / $perPage);

        $report = custom("
        SELECT *
        FROM order_report
        LIMIT $perPage  OFFSET $offset 
        ");


        foreach ($report as $key => $obj) {
            $report[$key] = $this->getDetail($obj['id']);
        }

        $res['totalCount'] = $total[0]['total'];
        $res['unChecked'] = $unchecked[0]['total'];
        $res['numOfPage'] =  $check;
        $res['page'] = $page;
        $res['obj'] = $report;

        return $res;
    }
}