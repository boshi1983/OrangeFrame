<?php

class MappingProcessor
{
    /**
     * @var RootMapping
     */
    private $classMapping;

    /**
     * @param BaseServer $server
     * @param DocParser $parser
     * @return void
     */
    public function init($server, $parser)
    {
        $mapperSaver = new MappingSaver($server, 'redis');
        $this->classMapping = $mapperSaver->get();
        if (empty($this->classMapping)) {
            $this->classMapping = (new ControllerLoader())->scan($parser);
            $mapperSaver->set($this->classMapping);
        }
    }

    /**
     * @param Request $request
     * @return MethodMapping
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
            $methodMapping = $this->classMapping->getGet($PHP_SELF);
        } elseif ($request->isPost()) {
            $methodMapping = $this->classMapping->getPost($PHP_SELF);
        }

        return $methodMapping;
    }
}