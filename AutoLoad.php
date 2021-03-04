<?php

/**
 * Created by PhpStorm.
 * User: caobo
 * Date: 16/3/23
 * Time: 上午10:12
 */

/**
 * Class AutoLoad
 */
class AutoLoad
{
    static $pathArr = [
        DS . 'demo' . DS,
        DS . 'demo' . DS . 'Controller' . DS,
        DS . 'demo' . DS . 'Plugin' . DS,
        DS . 'demo' . DS . 'DB' . DS,
        DS . 'demo' . DS . 'DB' . DS . 'DAO' . DS,
        DS . 'demo' . DS . 'DB' . DS . 'Bean' . DS,
        DS . 'src' . DS . 'class' . DS . 'aop' . DS,
        DS . 'src' . DS . 'class' . DS . 'ioc' . DS,
        DS . 'src' . DS . 'class' . DS . 'orm' . DS,
    ];

    /**
     * AutoLoad constructor.
     */
    public function __construct()
    {
    }


    /**
     *
     */
    public static function init()
    {
        spl_autoload_register([__CLASS__, 'autoload']);
    }

    /**
     * @param $class
     * @return bool
     */
    public static function autoload($class)
    {
        $arr = explode('\\', $class);
        $classfile = end($arr);

        foreach (self::$pathArr as $path) {
            if(empty($path))
                continue;
            $path = dirname(__FILE__) . $path . $classfile . '.php';
            if (file_exists($path)) {
                /** @noinspection PhpIncludeInspection */
                include_once($path);
                return true;
            }
        }

        return false;
    }
}