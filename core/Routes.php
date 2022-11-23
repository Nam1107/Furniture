<?php
class Routes
{
    public function UrlProcess()
    {
        //getURL
        $str = explode('?', $_SERVER['REQUEST_URI']);
        $strLower = strtolower($str[0]);
        $url = str_replace('/php/furniture', '', $strLower);

        //change URL to array
        $urlArr = array_filter(explode('/', $url));
        $urlArr = array_values($urlArr);

        if (empty($urlArr[0])) {
            http_response_code(404);
            $res['status'] = 0;
            $res['errors'] = "Not enough paramester";
            dd($res);
            exit();
        } else {
            $urlArr[0] = $urlArr[0] . "Controller";
        }
        return $urlArr;
    }
}