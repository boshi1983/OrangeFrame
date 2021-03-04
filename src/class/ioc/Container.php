<?php

class Container
{
    private static $instence;
    /**
     * @param string $name
     * @return object
     */
    public static function getObj(string $name) : object {
        return self::$instence->get($name);
    }

    /**
     * @var array
     */
    private $cache = [];

    /**
     * @var DocParser
     */
    private $parser;

    /**
     * @var OrangeBatis
     */
    private $orangeBatis;

    /**
     * Container constructor.
     */
    function __construct()
    {
        $this->parser = new DocParser();
        $this->orangeBatis = new OrangeBatis($this->parser);
        self::$instence = $this;
    }

    /**
     * 利用容器来实例化对象，外部调用接口
     * @param $name String
     * @return object
     * @throws ReflectionException|OrangeBatisException
     */
    public function get(string $name, array $params = []) : object
    {
        $key = md5($name . var_export(empty($params)?[]:$params, true));
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $partern = '/i([\w]+)Dao/';
        if (preg_match_all($partern, $name, $result)) {
            $batisClassName = $this->orangeBatis->getMapper($result[0][0]);
            $reflection = new ReflectionClass($batisClassName);
        } else {
            $reflection = new ReflectionClass($name);
        }

        $depends = $this->getDependency($reflection);
        $this->cache[$key] = $this->createObject($reflection, $depends, $params);
        unset($reflection);
        unset($depends);
        return $this->cache[$key];
    }

    /**
     * @param $name
     * @param $object
     */
    public function set($name, $object)
    {
        if(!isset($this->cache[$name])) {
            $this->cache[$name] = $object;
        }
    }

    /**
     * @param $name
     */
    public function del($name)
    {
        unset($this->cache[$name]);
    }

    /**
     * 利用反射获取类需要的依赖条件，注释中包含@inject 注解的public 变量
     * @param $reflection ReflectionClass
     * @return array
     */
    private function getDependency(ReflectionClass $reflection)
    {
        if(empty($reflection)) {
            return null;
        }

        $depends = [];
        $props = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
        foreach ($props as $prop) {
            $str = $prop->getDocComment();
            $anotations = $this->parser->parse($str);
            if (isset($anotations['inject']) && !empty($anotations['inject'])) {
                $depends[$prop->getName()] = [
                    'class' => $anotations['inject'],
                    'param' => $anotations['param'] ?? []
                ];
            }
            unset($anotations);
        }

        return $depends;
    }

    /**
     * 实例化对象的方法
     * @param $instance ReflectionClass
     * @param $depends array( 'field' => 'Class' ),  field 为注入的变量名，class为注入的类
     * @param $params array
     * @return object
     * @throws OrangeBatisException
     * @throws ReflectionException
     */
    public function &createObject(ReflectionClass $instance, array $depends, array $params = [])
    {
        $object = $instance->newInstanceArgs($params);
        if (!empty($depends)) {
            foreach ($depends as $key => $value) {

                $class = $value['class'];
                $subparam = $value['param'] ?? [];

                //区分数据访问层
                $partern = '/i([\w]+)Dao/';
                if (preg_match($partern, $class)) {
                    $class = $this->orangeBatis->getMapper($class);
                }
                $object->{$key} = $this->get($class, $subparam);
            }
        }
        return $object;
    }
}