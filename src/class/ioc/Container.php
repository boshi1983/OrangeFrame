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
     * Container constructor.
     */
    function __construct()
    {
        $this->parser = new DocParser();
        self::$instence = $this;
    }

    /**
     * 利用容器来实例化对象，外部调用接口
     * @param $name String
     * @return object
     * @throws ReflectionException|OrangeBatisException
     */
    public function get(string $name) : object
    {
        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        $partern = '/i([\w]+)Dao/';
        if (preg_match_all($partern, $name, $result)) {
            $batisClassName = OrangeBatis::getMapper($result[0][0]);
            $reflection = new ReflectionClass($batisClassName);
        } else {
            $reflection = new ReflectionClass($name);
        }

        $depends = $this->getDependency($reflection);
        $this->cache[$name] = $this->createObject($reflection, $depends);
        unset($reflection);
        unset($depends);
        return $this->cache[$name];
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
    public function getDependency(ReflectionClass $reflection)
    {
        if(empty($reflection)) {
            return null;
        }

        $depends = [];
        $props = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
        foreach ($props as $prop) {
            $str = $prop->getDocComment();
            $anotations = $this->parser->parse($str);
            if (isset($anotations['inject'])) {
                $depends[$prop->getName()] = $anotations['inject'];
            }
            unset($anotations);
        }

        return $depends;
    }

    /**
     * 实例化对象的方法
     * @param $instance ReflectionClass
     * @param $depends array( 'field' => 'Class' ),  field 为注入的变量名，class为注入的类
     * @return object
     * @throws OrangeBatisException|ReflectionException
     */
    public function createObject(ReflectionClass $instance, array $depends)
    {
        $object = $instance->newInstanceArgs([]);
        if (!empty($depends)) {
            foreach ($depends as $key => $value) {
                //区分数据访问层
                $partern = '/i([\w]+)Dao/';
                if (preg_match($partern, $value)) {
                    $value = OrangeBatis::getMapper($value);
                }

                $object->{$key} = $this->get($value);
            }
        }
        return $object;
    }
}