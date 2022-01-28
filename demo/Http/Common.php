<?php

class Common
{
    /**
     * @var BaseServer
     */
    protected $server;

    public function __construct($server)
    {
        $this->server = $server;
    }

    function &getArray(&$obj, $key, $filter, $default)
    {
        if (isset($obj[$key])) {
            if (is_array($obj[$key])) {
                $value = $obj[$key];
            } else {
                switch ($filter) {
                    case 'raw':
                        $value = $obj[$key];
                        break;
                    case 'trim':
                        $value = trim($obj[$key]);
                        break;
                    case 'intval':
                        $value = intval($obj[$key]);
                        break;
                    case 'upper':
                        $value = strtoupper(trim($obj[$key]));
                        break;
                    case 'lower':
                        $value = strtolower(trim($obj[$key]));
                        break;
                    default:
                        $value = $filter($obj[$key]);
                        break;
                }
            }
            return $value;
        } else {
            return $default;
        }
    }

    /**
     * @param $obj
     * @param $key
     * @param $filter
     * @param $default
     * @return mixed
     */
    function &getObject($obj, $key, $filter, $default)
    {
        if (isset($obj->{$key})) {
            if (is_array($obj->{$key})) {
                $value = $obj->{$key};
            } else {
                switch ($filter) {
                    case 'raw':
                        $value = $obj->{$key};
                        break;
                    case 'trim':
                        $value = trim($obj->{$key});
                        break;
                    case 'intval':
                        $value = intval($obj->{$key});
                        break;
                    case 'upper':
                        $value = strtoupper(trim($obj->{$key}));
                        break;
                    case 'lower':
                        $value = strtolower(trim($obj->{$key}));
                        break;
                    default:
                        $value = $filter($obj->{$key});
                        break;
                }
            }
            return $value;
        } else {
            return $default;
        }
    }

    /**
     * @param $obj
     * @param $key
     * @param string $filter
     * @param string $default
     * @return mixed
     */
    function &_get(&$obj, $key, $filter = 'trim', $default = '')
    {
        if (empty($obj))
            return $default;

        if (is_array($obj)) {
            return $this->getArray($obj, $key, $filter, $default);
        } elseif (is_object($obj)) {
            return $this->getObject($obj, $key, $filter, $default);
        }

        return $default;
    }

    /**
     * @param $obj
     * @param $key
     * @param $value
     * @return void
     */
    function _set(&$obj, $key, $value)
    {
        if (is_array($obj)) {
            $obj[$key] = $value;
        } elseif (is_object($obj)) {
            $obj->{$key} = $value;
        }
    }
}