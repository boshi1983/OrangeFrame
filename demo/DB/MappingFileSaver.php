<?php

class MappingFileSaver implements iMappingSaver
{
    const FILE = 'mapping.php';

    public function __construct($server)
    {
    }

    function read()
    {
        $path = ROOT_PATH . self::FILE;
        if (file_exists($path)) {
            return include $path;
        }
        return '';
    }

    function write($data)
    {
        $path = ROOT_PATH . self::FILE;
        file_put_contents($path, '<?php return ' . var_export($data, true) . ';');
    }

}