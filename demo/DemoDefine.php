<?php


class DemoDefine
{
    /**/
    const REDIS_MASTER = 'master';
    const REDIS_SLAVE = 'slave';

    public static function isAjax()
    {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            if ('xmlhttprequest' == strtolower($_SERVER['HTTP_X_REQUESTED_WITH']))
                return true;
        }
        if (!empty($_GET['callback']) && strlen($_GET['callback']) > 6 && strpos($_GET['callback'], 'jQuery') == 0)
            // 判断Ajax方式提交
            return true;
        return false;
    }

    public static function isPost()
    {
        return (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST');
    }
}