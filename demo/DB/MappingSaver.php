<?php

class MappingSaver
{
    /**
     * @var iMappingSaver
     */
    protected $iSaver;

    /**
     * @param BaseServer $server
     * @param string $type
     */
    public function __construct($server, $type)
    {
        $saverName = 'Mapping' . ucfirst($type) . 'Saver';
        $this->iSaver = new $saverName($server);
    }

    public function get() {
        $content = $this->iSaver->read();
        if (!empty($content)) {
            return $this->parseJson($content);
        }
        return null;
    }

    private function parseJson($json) {
        $root = new RootMapping();

        foreach ($json['get'] as $path => $method) {
            $methodMapping = new MethodMapping();
            $methodMapping->setByJson($method);

            $root->addGet($path, $methodMapping);
        }

        foreach ($json['post'] as $path => $method) {
            $methodMapping = new MethodMapping();

            $root->addPost($path, $methodMapping);
        }
        return $root;
    }

    public function set(RootMapping $mapping) {
        $this->iSaver->write($mapping->genDataMap());
    }
}