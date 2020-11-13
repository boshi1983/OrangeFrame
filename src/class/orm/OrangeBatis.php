<?php

class OrangeBatisException extends Exception
{
}

class OrangeBatis
{
    //是否强制编译代理类文件
    static $forceCompile = false;
    static $dirCompile = 'ProxyTemp/';
    static $dirXml = 'DB/XmlModel/';

    /**
     * 反射内存池
     * @var array
     */
    private $reflectionList = [];

    /**
     * @var DocParser
     */
    private $parser;

    /**
     * OrangeBatis constructor.
     * @param DocParser $parser
     */
    public function __construct(DocParser $parser)
    {
        $this->parser = $parser;
    }


    /**
     * 下划线转驼峰
     * 思路:
     * step1.原字符串转小写,原字符串中的分隔符用空格替换,在字符串开头加上分隔符
     * step2.将字符串中每个单词的首字母转换为大写,再去空格,去字符串首部附加的分隔符.
     * @param $uncamelized_words
     * @param string $separator
     * @param false $firstup
     * @return string
     */
    public static function camelize($uncamelized_words,$separator='_', $firstup = false)
    {
        $uncamelized_words = $separator. str_replace($separator, " ", strtolower($uncamelized_words));
        $rt = ltrim(str_replace(" ", "", ucwords($uncamelized_words)), $separator );
        if ($firstup) {
            $rt = ucfirst($rt);
        }
        return $rt;
    }

    /**
     * 驼峰命名转下划线命名
     * 思路:
     * 小写和大写紧挨一起的地方,加上分隔符,然后全部转小写
     * @param $camelCaps
     * @param string $separator
     * @return string
     */
    public static function uncamelize($camelCaps, $separator='_')
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", $camelCaps));
    }

    /**
     * @param ReflectionType $className
     * @return string
     */
    private function getClassShortName(ReflectionType $type)
    {
        $rt = '';
        if ($type->allowsNull()) {
            $rt .= '?';
        }
        return $rt . basename(str_replace('\\', '/', $type-> getName()));
    }

    /**
     * 获取反射函数
     * @param string $className 类名
     * @return mixed
     * @throws OrangeBatisException
     */
    private function getReflectionByClassName(string $className)
    {
        if (!isset($this->reflectionList[$className]) || empty($this->reflectionList[$className])) {
            try {
                $this->reflectionList[$className] = new ReflectionClass($className);
            } catch (Exception $e) {
                throw new OrangeBatisException($e);
            }
        }
        return $this->reflectionList[$className];
    }

    /**
     * 生成ORM操作类
     * @param string $interFaceName 接口类名
     * @throws OrangeBatisException
     */
    private function generatedClass(string $interFaceName)
    {
        $xmlName = substr($interFaceName, 1, strlen($interFaceName) - 4);
        $xmlContent = $this->parseXml($xmlName);

        $reflection = $this->getReflectionByClassName($interFaceName);
        $baseReflextion = $this->getReflectionByClassName('BaseBatisProxy');

        $methodList = $reflection->getMethods();
        $functionList = [];
        foreach ($methodList as $method) {
            $methodName = $method->getName();
            $hasMethod = $baseReflextion->hasMethod($methodName);
            if (!$hasMethod) {
                $xmlNode = $xmlContent[$methodName] ?? null;
                if (!empty($xmlNode)) {
                    $functionList[] = $this->generateMethod($method, $xmlNode);
                } else {
                    throw new OrangeBatisException('not find decleared ' . $methodName . ' defined!');
                }
            }
        }

        $class = 'final class ' . $xmlName . 'Proxy extends BaseBatisProxy implements ' . $interFaceName . ' {' . PHP_EOL;
        $class .= 'const VERSION = ' . microtime(true) . ';' . PHP_EOL;
        $class .= join(PHP_EOL . PHP_EOL, $functionList) . PHP_EOL;

        $class .= '}' . PHP_EOL;

        $path = ROOT_PATH . self::$dirCompile . $xmlName . 'Proxy.php';
        file_put_contents($path, '<?php' . PHP_EOL . $class);

        if (function_exists('opcache_invalidate')
            && (!function_exists('ini_get') || strlen(ini_get("opcache.restrict_api")) < 1)
        ) {
            opcache_invalidate($path, true);
        } elseif (function_exists('apc_compile_file')) {
            apc_compile_file($path);
        }

        //加载动态代理类
        eval($class);
    }

    /**
     * 把查询出来的数据，赋值到对象
     * @param XmlNode $xmlNode xml节点
     * @param ReflectionType $returnType
     * @param string $rtName
     * @return string
     * @throws OrangeBatisException
     */
    private function setValue(XmlNode $xmlNode, ?ReflectionType $returnType, string &$rtName)
    {
        if (empty($returnType)) {
            return '';
        }

        $class = $xmlNode->getResultType();

        $value = '$row';
        $obj = '';
        switch ($returnType->getName()) {
            case 'array':
                $obj .= '$result = $this->query($sql);' . PHP_EOL;
                $obj .= '$list = [];' . PHP_EOL;
                $obj .= 'if(is_array($result)) { foreach ($result as $row) {' . PHP_EOL;
                $obj .= $this->row2Object($class, $value);
                $obj .= '$list[] = $obj;' . PHP_EOL;
                $obj .= '}}' . PHP_EOL;
                $rtName = '$list';
                break;
            default:
                $obj .= $value . ' = $this->get($sql);' . PHP_EOL;
                $obj .= $this->row2Object($class, $value);
                $rtName = '$obj';
                break;
        }

        return $obj;
    }

    /**
     * 生成行转对象的代码段
     * @param string $class
     * @param string $value
     * @return string
     * @throws OrangeBatisException
     */
    private function row2Object(string $class, string $value)
    {
        $obj = '$obj = new ' . $class . '();' . PHP_EOL;
        $obj .= 'if(!empty('.$value.')) {' . PHP_EOL;
        $reflection = $this->getReflectionByClassName($class);
        $methodList = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        foreach ($methodList as $method) {
            $name = $method->getName();
            if (strpos($name, 'set') !== 0)
                continue;

            $varname = self::uncamelize(substr($name, 3));
            $obj .= '$obj->' . $name . '(' . $value . '[\'' . $varname . '\']??'.$this->getDefaultByPropertyType($reflection, $varname).');' . PHP_EOL;
        }
        $obj .= '}' . PHP_EOL;

        return $obj;
    }


    /**
     * @param ReflectionClass $reflection
     * @throws ReflectionException
     */
    private function getDefaultByPropertyType(ReflectionClass $reflection, string $name)
    {
        $property = $reflection->getProperty($name);
        if (empty($property)) {
            return '\'\'';
        }
        $doc = $property->getDocComment();
        if (empty($doc)) {
            return '\'\'';
        }

        $anotations = $this->parser->parse($doc);
        if (empty($anotations) || !isset($anotations['var'])) {
            return '\'\'';
        }

        switch ($anotations['var']) {
            case 'int':
            case 'float':
                return 0;
            case 'array':
                return '[]';
            case 'string':
            default:
                return '\'\'';
        }
    }

    /**
     * 准备sql bind参数
     * @param $sql
     * @param $methodName
     * @param $varname
     * @param $value
     * @return string
     */
    private function getValueBySql($sql, $methodName, $varname, $value)
    {
        $obj = '';
        if (strpos($sql, ':' . $varname) !== false) {
            $obj .= 'if (!is_null(' . $value . '->' . $methodName . '())) {' . PHP_EOL;
            $obj .= '$this->bindParam(\'' . $varname . '\', ' . $value . '->' . $methodName . '());' . PHP_EOL;
            $obj .= '}' . PHP_EOL;
        }

        return $obj;
    }

    /**
     * 准备sql bind参数
     * @param $value
     * @param $class
     * @param $sql
     * @return string
     * @throws OrangeBatisException
     */
    private function getValue($value, $class, $sql)
    {
        if (empty($class)) {
            $class = 'string';
        }

        $obj = '';
        $reflection = $this->getReflectionByClassName($class);
        $methodList = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        foreach ($methodList as $method) {
            $methodName = $method->getName();
            if (strpos($methodName, 'get') !== 0)
                continue;

            $varname = self::uncamelize(substr($methodName, 3));
            $obj .= $this->getValueBySql($sql, $methodName, $varname, $value);
        }

        return $obj;
    }

    /**
     * 解析xml
     * @param $xmlName
     * @return array
     * @throws OrangeBatisException
     */
    private function parseXml($xmlName)
    {
        $path = ROOT_PATH . self::$dirXml . self::uncamelize($xmlName) . '.xml';
        $mapper = simplexml_load_file($path);
        if (empty($mapper)) {
            throw new OrangeBatisException(libxml_get_errors());
        }

        $xmlContents = [];
        foreach ($mapper->children() as $key => $value) {

            $id = trim($value->attributes()['id']);
            if ($value->children()->count() > 0) {
                $xmlContent = new XmlNode($id, $key, trim($value->attributes()['resultType']), $value->attributes()['transaction']);

                $arr = explode("\n", trim($value));
                if (count($arr) <= 1) {
                    $arr[] = '';
                }

                $sql = '';
                $paramid = 0;
                $funcs = [];
                foreach ($arr as $data) {

                    $data = trim($data);
                    if (empty($data)) {
                        $node = $value->children()[$paramid];

                        $sql .= ' \' . $param' . $paramid . ' . \' ';

                        $func = '$param'.$paramid.' = $this->'.$node->getName().'(';
                        switch ($node->getName()) {
                            case 'foreach':
                                $collection = trim($node->attributes()['collection']);
                                $item = trim($node->attributes()['item']);
                                $open = trim($node->attributes()['open']);
                                $separator = trim($node->attributes()['separator']);
                                $close = trim($node->attributes()['close']);

                                $content = trim($node);

                                $func .= $collection . ',\'' . $item . '\',\'' . $content . '\',\'' . $open . '\',\'' . $separator . '\',\'' . $close . '\'';
                                break;
                            default:
                                break;
                        }

                        $func .= ');' . PHP_EOL;

                        $funcs[] = $func;
                        ++$paramid;
                    } else {
                        $sql .= $data;
                    }
                }

                $xmlContent->setFunc($funcs);
                $xmlContent->setSql($sql);
            } else {
                $xmlContent = new XmlNode($id, $key, trim($value->attributes()['resultType']), $value->attributes()['transaction']);
                $xmlContent->setSql(trim($value));
            }
            $xmlContents[$id] = $xmlContent;
        }

        return $xmlContents;
    }

    /**
     * 生成ORM操作方法
     * @param ReflectionMethod $method
     * @param XmlNode $xmlNode
     * @return string
     * @throws OrangeBatisException
     */
    private function generateMethod(ReflectionMethod $method, XmlNode $xmlNode)
    {
        $function = 'public function ' . $method->getName() . '(';

        $reflectionParameters = $method->getParameters();
        $paramList = [];
        $paramArr = [];
        foreach ($reflectionParameters as $parameter) {
            $paramStr = '';
            $param = ['name' => '', 'type' => ''];

            $type = $parameter->getType();
            if (!is_null($type)) {
                $className = $this->getClassShortName($type);
                $paramStr .= $className . ' ';

                $param['type'] = $className;
            }
            $paramStr .= '$' . $parameter->getName();

            $paramArr[] = $paramStr;

            $param['name'] = trim($parameter->getName());
            $paramList[] = $param;
        }

        $function .= join(', ', $paramArr);

        $function .= ')';
        $return = $method->getReturnType();
        if (!is_null($return)) {
            $function .= ':' . $this->getClassShortName($return);
        }

        $function .= '{' . PHP_EOL;
        $function .= $this->generateMethodContent($method, $paramList, $xmlNode);
        $function .= '}' . PHP_EOL;

        return $function;
    }

    /**
     * 生成操作方法体
     * @param $method
     * @param $paramList
     * @param XmlNode $xmlNode
     * @return string
     * @throws OrangeBatisException
     */
    public function generateMethodContent(ReflectionMethod $method, array $paramList, XmlNode $xmlNode)
    {
        $content = '';

        $transaction = false;
        if ($xmlNode->getTransaction()) {
            $transaction = true;
            $content .= '$this->begin();' . PHP_EOL;
        }

        if (is_array($xmlNode->getFunc())) {
            $content .= join('', $xmlNode->getFunc());
        }
        $content .= '$sql = \'' . $xmlNode->getSql() . '\';' . PHP_EOL;

        foreach ($paramList as $param) {
            if (in_array($param['type'], ['int', 'string', 'float', 'double', 'bool', 'boolean', ''])) {
                if (strpos($xmlNode->getSql(), ':' . $param['name']) !== false) {
                    $content .= '$this->bindParam(\'' . $param['name'] . '\', $' . $param['name'] . ');' . PHP_EOL;
                }
            } elseif (in_array($param['type'], ['array', 'object'])) {
                $content .= 'foreach ($' . $param['name'] . ' as $key => $value) {' . PHP_EOL;
                $content .= 'if (strpos($sql, \':\' . $key) !== false) {' . PHP_EOL;
                $content .= '$this->bindParam($key, $value);' . PHP_EOL;
                $content .= '}' . PHP_EOL;
                $content .= '}' . PHP_EOL;
            } else {
                $content .= $this->getValue('$' . $param['name'], $param['type'], $xmlNode->getSql()) . PHP_EOL;
            }
        }

        $rtName = '$rt';

        $methodReturnType = null;
        if (!empty($method->getReturnType())) {
            $methodReturnType = $method->getReturnType()->getName();
        }

        switch ($xmlNode->getTag())
        {
            case 'select':
                switch ($methodReturnType) {
                    case 'int':
                        $content .= ($transaction?'$rt =':'return') . ' intval($this->getOne($sql));' . PHP_EOL;
                        break;
                    case 'string':
                        $content .= ($transaction?'$rt =':'return') . ' \'\' . $this->getOne($sql);' . PHP_EOL;
                        break;
                    case 'float':
                        $content .= ($transaction?'$rt =':'return') . ' floatval($this->getOne($sql));' . PHP_EOL;
                        break;
                    default:
                        $content .= $this->setValue($xmlNode, $method->getReturnType(), $rtName);
                        if (!$transaction) {
                            $content .= 'return ' . $rtName . ';' . PHP_EOL;
                        }
                        break;
                }
                break;
            case 'insert':
                $content .= '$this->execute($sql);' . PHP_EOL;
                $content .= ($transaction?'$rt =':'return') . ' $this->getLastInsID();' . PHP_EOL;
                break;
            default:
                //update, delete
                $content .= ($transaction?'$rt =':'return') . ' $this->execute($sql);' . PHP_EOL;
                break;
        }

        if ($transaction) {
            $content .= 'return $this->commit()? '.$rtName.' : ';

            switch ($methodReturnType) {
                case 'int':$content .= '0;';break;
                case 'float':$content .= '0.0;';break;
                case 'array':$content .= '[];';break;
                case '':case 'string':$content .= '\'\';';break;
                default:$content .= 'null;';break;
            }
        }

        return $content;
    }

    //--------------------------------------------------------------------------------------------------------------

    /**
     * @param $path
     * @param $xml
     * @return bool
     * @throws OrangeBatisException
     */
    private static function checkFileTime($path, $xml)
    {
        $thisTime = filemtime(__FILE__);
        $pathTime = file_exists($path) ? filemtime($path) : 0;
        $xmlTime = file_exists($xml) ? filemtime($xml) : 0;
        if (empty($xmlTime)) {
            throw new OrangeBatisException('xml "'.$xml.'" is not found!');
        }

        return ($pathTime >= max($thisTime, $xmlTime));
    }

    /**
     * @param string $interFaceName
     * @return object|string
     * @throws OrangeBatisException
     * @throws OrangeBatisException
     */
    public function getMapper(string $interFaceName)
    {
        //去掉前缀i和后缀Dao
        //$xmlName = substr($interFaceName, 1, strlen($interFaceName) - 4);
        $xmlName = '';
        $partern = '/i([\w]+)Dao/';
        if (preg_match_all($partern, $interFaceName, $result)) {
            $xmlName = $result[1][0];
        }

        if (empty($xmlName)) {
            throw new OrangeBatisException($interFaceName . ' is not match i*Dao!');
        }

        $className = $xmlName . 'Proxy';
        $path = ROOT_PATH . self::$dirCompile . $className . '.php';
        $xml = ROOT_PATH . self::$dirXml . self::uncamelize($xmlName) . '.xml';

        if (self::$forceCompile || !file_exists($path) || !self::checkFileTime($path, $xml)) {
            //强制编译 或者 编译文件不存在
            $this->generatedClass($interFaceName);
        } else {
            /** @noinspection PhpIncludeInspection */
            require_once $path;
        }

        return $className;
    }
}
