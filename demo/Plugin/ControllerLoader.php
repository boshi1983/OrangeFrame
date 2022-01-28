<?php

class ClassMapping
{
    private $path;
    private $name;
    private $getMapping;
    private $postMapping;
    private $requestMapping;

    public function fullGetMapping() {
        $mapping = $this->getMapping;
        if (empty($mapping)) {
            $mapping = $this->requestMapping;
        }

        return $mapping;
    }

    public function fullPostMapping() {
        $mapping = $this->postMapping;
        if (empty($mapping)) {
            $mapping = $this->requestMapping;
        }

        return $mapping;
    }

    /**
     * @return mixed
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param mixed $path
     */
    public function setPath($path): void
    {
        $this->path = $path;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getGetMapping()
    {
        return $this->getMapping;
    }

    /**
     * @param mixed $getMapping
     */
    public function setGetMapping($getMapping): void
    {
        $this->getMapping = $getMapping;
    }

    /**
     * @return mixed
     */
    public function getPostMapping()
    {
        return $this->postMapping;
    }

    /**
     * @param mixed $postMapping
     */
    public function setPostMapping($postMapping): void
    {
        $this->postMapping = $postMapping;
    }

    /**
     * @return mixed
     */
    public function getRequestMapping()
    {
        return $this->requestMapping;
    }

    /**
     * @param mixed $requestMapping
     */
    public function setRequestMapping($requestMapping): void
    {
        $this->requestMapping = $requestMapping;
    }
}

class MethodMapping {
    /**
     * @var ClassMapping
     */
    private $classMapping;
    private $name;
    private $getMapping;
    private $postMapping;
    private $requestMapping;

    public function fullGetMapping() {
        $mapping = $this->getMapping;
        if (empty($mapping)) {
            $mapping = $this->requestMapping;
        }

        if (empty($mapping)) {
            return '';
        }

        return $this->classMapping->fullGetMapping() . $mapping;
    }

    public function fullPostMapping() {
        $mapping = $this->postMapping;
        if (empty($mapping)) {
            $mapping = $this->requestMapping;
        }

        if (empty($mapping)) {
            return '';
        }
        return $this->classMapping->fullPostMapping() . $mapping;
    }

    /**
     * @return mixed
     */
    public function getClassMapping()
    {
        return $this->classMapping;
    }

    /**
     * @param mixed $classMapping
     */
    public function setClassMapping($classMapping): void
    {
        $this->classMapping = $classMapping;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getGetMapping()
    {
        return $this->getMapping;
    }

    /**
     * @param mixed $getMapping
     */
    public function setGetMapping($getMapping): void
    {
        $this->getMapping = $getMapping;
    }

    /**
     * @return mixed
     */
    public function getPostMapping()
    {
        return $this->postMapping;
    }

    /**
     * @param mixed $postMapping
     */
    public function setPostMapping($postMapping): void
    {
        $this->postMapping = $postMapping;
    }

    /**
     * @return mixed
     */
    public function getRequestMapping()
    {
        return $this->requestMapping;
    }

    /**
     * @param mixed $requestMapping
     */
    public function setRequestMapping($requestMapping): void
    {
        $this->requestMapping = $requestMapping;
    }
}

/**
 * 扫描CONTROLLER_PATH目录，获取请求path与响应函数对应关系
 */
class ControllerLoader
{
    /**
     * @var DocParser
     */
    private $parser;

    /**
     * @var array
     */
    private $classMapping = ['get' => [], 'post' => []];

    public function __construct()
    {
        $this->parser = new DocParser();
    }

    public function scan() {
        $all_files = $this->get_all_files(CONTROLLER_PATH);

        array_walk($all_files, [$this, 'scanClass']);
    }

    private function scanClass($filepath) {
        include_once($filepath);
        $className = basename($filepath, '.php');
        $reflectionClass = new ReflectionClass($className);
        $parse = $this->parser->parse($reflectionClass->getDocComment());
        if (empty($parse))
            return;

        if (!isset($parse['Controller']))
            return;

        $classMapping = new ClassMapping();

        $classMapping->setPath($filepath);
        $classMapping->setName($className);
        $classMapping->setGetMapping($parse['GetMapping'] ?? '');
        $classMapping->setPostMapping($parse['PostMapping'] ?? '');
        $classMapping->setRequestMapping($parse['RequestMapping'] ?? '');

        $reflectionMethods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);
        foreach ($reflectionMethods as $reflectionMethod) {
            $scanMethod = $this->scanMethod($reflectionMethod);
            if (!empty($scanMethod)) {
                $scanMethod->setClassMapping($classMapping);

                $getMapping = $scanMethod->fullGetMapping();
                if (!empty($getMapping)) {
                    $this->classMapping['get'][$getMapping] = $scanMethod;
                }
                $postMapping = $scanMethod->fullPostMapping();
                if (!empty($postMapping)) {
                    $this->classMapping['post'][$postMapping] = $scanMethod;
                }
            }
        }
    }

    private function scanMethod(ReflectionMethod $reflectionMethod) {
        $parse = $this->parser->parse($reflectionMethod->getDocComment());
        $getMapping = $parse['GetMapping'] ?? '';
        $postMapping = $parse['PostMapping'] ?? '';
        $requestMapping = $parse['RequestMapping'] ?? '';

        if (empty($getMapping) && empty($postMapping) && empty($requestMapping)) {
            return null;
        }

        $methodMapping = new MethodMapping();
        $methodMapping->setName($reflectionMethod->getName());
        $methodMapping->setGetMapping($getMapping);
        $methodMapping->setPostMapping($postMapping);
        $methodMapping->setRequestMapping($requestMapping);

        return $methodMapping;
    }

    /**
     * @param $path
     * @param $callback
     * @return array
     */
    private function get_all_files( $path ){
        clearstatcache();
        $list = [];

        foreach( glob( $path . '*') as $item ){
            if( is_dir( $item ) ){
                $list = array_merge( $list , $this->get_all_files( $item . '/' ) );
            } else {
                $list[] = $item;
            }
        }
        return $list;
    }

    /**
     * @param Request $request
     * @return BaseController
     */
    public function get(Request $request)
    {
        $PHP_SELF = $request->Server('PHP_SELF');
        if (strlen($PHP_SELF) > 1) {
            $PHP_SELF = substr($PHP_SELF, 1);
        } else {
            $PHP_SELF = '/';
        }

        $methodMapping = null;
        if ($request->isGet()) {
            $methodMapping = $this->classMapping['get'][$PHP_SELF] ?? null;
        } elseif ($request->isPost()) {
            $methodMapping = $this->classMapping['post'][$PHP_SELF] ?? null;
        }

        return $methodMapping;
    }
}