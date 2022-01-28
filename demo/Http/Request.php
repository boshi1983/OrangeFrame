<?php

class Request extends Common
{
    protected $clientData = null;

    /**
     * @return null
     */
    public function getClientData()
    {
        return $this->clientData;
    }

    /**
     * @param null $clientData
     */
    public function setClientData($clientData): void
    {
        $this->clientData = $clientData;
    }

    /**
     * @param $key
     * @param $filter
     * @param $default
     * @return array|mixed|string
     */
    function Server($key, $filter = 'trim', $default = '')
    {
        if (empty($key)) {
            return $_SERVER;
        } else {
            return $this->_get($_SERVER, $key, $filter, $default);
        }
    }

    /**
     * @return bool
     */
    public function isGet() {
        $REQUEST_METHOD = $this->Server('REQUEST_METHOD');
        return (!empty($REQUEST_METHOD) && !strcasecmp($REQUEST_METHOD, 'GET'));
    }

    /**
     * @return bool
     */
    public function isPost() {
        $REQUEST_METHOD = $this->Server('REQUEST_METHOD');
        return (!empty($REQUEST_METHOD) && !strcasecmp($REQUEST_METHOD, 'POST'));
    }

    /**
     * @param $key
     * @param $filter
     * @param $default
     * @return array|mixed|string
     */
    public function param($key, $filter = 'trim', $default = '') {
        if ($this->isAjax() || $this->isPost()) {

            if (isset($_POST[$key])) {
                return $this->_get($_POST, $key, $filter, $default);
            } else {
                return $this->_get($this->clientData, $key, $filter, $default);
            }
        } elseif ($this->isGet()) {
            return $this->_get($_GET, $key, $filter, $default);
        }else {
            return $this->_get($_REQUEST, $key, $filter, $default);
        }
    }

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
}