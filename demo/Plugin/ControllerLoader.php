<?php

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
     * @var RootMapping
     */
    private $classMapping;

    public function __destruct()
    {
        $this->parser = null;
        $this->classMapping = null;
    }

    /**
     * @param DocParser $parser
     * @return RootMapping
     */
    public function scan(DocParser $parser) {
        $this->classMapping = new RootMapping();
        $this->parser = $parser;
        $all_files = $this->get_all_files(CONTROLLER_PATH);
        array_walk($all_files, [$this, 'scanClass']);

        return $this->classMapping;
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
                    $this->classMapping->addGet($getMapping, $scanMethod);
                }
                $postMapping = $scanMethod->fullPostMapping();
                if (!empty($postMapping)) {
                    $this->classMapping->addPost($postMapping, $scanMethod);
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
}