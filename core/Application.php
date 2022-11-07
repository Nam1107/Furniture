<?php
require "./core/Controllers.php";
class Application extends Controllers
{
    private $controller;
    private $action;
    private $prarams;

    public function __construct()
    {
        global $router;
        $this->controller = '';
        $this->action = '';
        $this->prarams = [];
        $this->handleUrl();
    }

    public function handleUrl()
    {
        $arr = $this->UrlProcess();
        if (empty($arr[0])) {
            $this->loadErrors(404, "Not enough paramester");
        }

        if (file_exists("./app/controllers/" . $arr[0] . ".php")) {
            $this->controller = $arr[0];
            require "./app/controllers/" . $arr[0] . ".php";
            $this->controller = new $this->controller();
            unset($arr[0]);
        } else {
            $this->loadErrors(404, "Not found '$arr[0]'");
        }

        if (!empty($arr[1])) {
            if (method_exists($this->controller, $arr[1])) {
                $this->action = $arr[1];
                unset($arr[1]);
            } else {
                $this->loadErrors(404, "Not found '$arr[1]'");
            }
        }

        $this->prarams = array_values($arr);
        call_user_func_array([$this->controller, $this->action], $this->prarams);
    }

    public function UrlProcess()
    {
        //getURL
        $str = explode('?', $_SERVER['REQUEST_URI']);
        $strLower = strtolower($str[0]);
        $url = str_replace('/php/furniture', '', $strLower);

        //change URL to array
        $urlArr = array_filter(explode('/', $url));
        $urlArr = array_values($urlArr);
        return $urlArr;
    }
}