<?php

class OrangeBatisException extends Exception
{
}

class BaseReflection
{
    /**
     * 反射内存池
     * @var array
     */
    private static $reflectionList = [];

    /**
     * @var string
     */
    private $namespace = '';

    /**
     * 获取反射函数
     * @param string $className 类名
     * @return ReflectionClass
     * @throws OrangeBatisException
     */
    protected function getReflectionByClassName(string $className)
    {
        $className = $this->classNameSpace($className);

        if (!isset(self::$reflectionList[$className]) || empty(self::$reflectionList[$className])) {
            try {
                self::$reflectionList[$className] = new ReflectionClass($className);
            } catch (Exception $e) {
                throw new OrangeBatisException($e);
            }
        }
        return self::$reflectionList[$className];
    }

    /**
     * 处理类的命名空间
     * @param string $className
     * @return string
     */
    protected function classNameSpace(string $className)
    {
        if(!empty($this->namespace) && strpos($className, $this->namespace . '\\') !== 0) {
            return $this->namespace . '\\' . $className;
        }
        return $className;
    }

    /**
     * 设置类的命名空间
     * @param string $namespace
     */
    protected function setNameSpace(string $namespace)
    {
        $this->namespace = $namespace;
    }
}

class OrangeBatis extends BaseReflection
{
    //是否强制编译代理类文件
    static $forceCompile = true;
    static $dirCompile = 'ProxyTemp/';
    static $dirXml = 'DB/XmlModel/';

    /**
     * @var DocParser
     */
    private $parser;

    /**
     * @var Iterator
     */
    private $xmlIterator = null;

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
     * @param ReflectionType $type
     * @return string
     */
    private function getClassShortName(ReflectionType $type): string
    {
        $rt = '';
        if ($type->allowsNull()) {
            $rt .= '?';
        }
        return $rt . basename(str_replace('\\', '/', $type->getName()));
    }

    /**
     * 生成ORM操作类
     * @param string $interFaceName 接口类名
     * @throws OrangeBatisException|ReflectionException
     */
    private function generatedClass(string $interFaceName)
    {
        $xmlName = substr($interFaceName, 1, strlen($interFaceName) - 4);
        list($namespace, $xmlContent, $varContents) = $this->parseXml($xmlName);

        $reflection = $this->getReflectionByClassName($interFaceName);
        $baseReflextion = $this->getReflectionByClassName('BaseBatisProxy');

        $class = '';
        if (!empty($namespace)) {
            $class .= 'namespace ' . $namespace . '{' . PHP_EOL;
        }
        $class .= 'final class ' . $xmlName . 'Proxy extends BaseBatisProxy implements ' . $interFaceName . ' {' . PHP_EOL;
        $class .= 'const VERSION = ' . microtime(true) . ';' . PHP_EOL;

        foreach ($varContents as $varContent) {
            $class .= 'protected $' . $varContent->getId() . ' = \''.$varContent->getChildren(0).'\';' . PHP_EOL;
        }

        $methodList = $reflection->getMethods();
        foreach ($methodList as $method) {
            $methodName = $method->getName();
            $hasMethod = $baseReflextion->hasMethod($methodName);
            if (!$hasMethod) {
                $xmlNode = $xmlContent[$methodName] ?? null;
                if (!empty($xmlNode)) {
                    $class .= $this->generateMethod($method, $xmlNode) . PHP_EOL;
                } else {
                    throw new OrangeBatisException('not find decleared ' . $methodName . ' defined!');
                }
            }
        }

        $class .= '}' . PHP_EOL;
        if (!empty($namespace)) {
            $class .= '}' . PHP_EOL;
        }

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
     * @param ReflectionType|null $returnType
     * @param string $rtName
     * @return string
     * @throws OrangeBatisException
     * @throws ReflectionException
     */
    private function setValue(XmlNode $xmlNode, ?ReflectionType $returnType, string &$rtName): string
    {
        if (empty($returnType)) {
            return '';
        }

        $class = $xmlNode->getResultType();

        $value = '$row';
        $obj = '';
        switch ($returnType->getName()) {
            case 'array':
                $rtName = '$list';
                $obj .= '$result = $this->query($sql);' . PHP_EOL;
                $obj .= $rtName . ' = [];' . PHP_EOL;
                $obj .= 'if(is_array($result)) { foreach ($result as $row) {' . PHP_EOL;
                $obj .= $this->row2Object($class, $value, '$obj');
                $obj .= $rtName . '[] = $obj;' . PHP_EOL;
                $obj .= '}}' . PHP_EOL;
                break;
            default:
                $rtName = '$obj';
                $obj .= $value . ' = $this->get($sql);' . PHP_EOL;
                $obj .= $this->row2Object($class, $value, $rtName);
                break;
        }

        return $obj;
    }

    /**
     * 生成行转对象的代码段
     * @param string $class
     * @param string $value
     * @param string $returnName
     * @return string
     * @throws OrangeBatisException
     */
    private function row2Object(string $class, string $value, string $returnName): string
    {
        $obj = $returnName . ' = new ' . $class . '();' . PHP_EOL;
        $obj .= 'if(!empty('.$value.')) {' . PHP_EOL;
        $reflection = $this->getReflectionByClassName($class);

        $methodList = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        foreach ($methodList as $method) {
            $name = $method->getName();
            if (strpos($name, 'set') !== 0)
                continue;

            $varname = self::uncamelize(substr($name, 3));
            $obj .= $returnName . '->' . $name . '(' . $value . '[\'' . $varname . '\']??' . $this->getDefaultByPropertyType($reflection, $varname) . ');' . PHP_EOL;
        }
        $obj .= '}' . PHP_EOL;

        return $obj;
    }

    /**
     * @param ReflectionClass $reflection
     * @param string $name
     * @return int|string
     */
    private function getDefaultByPropertyType(ReflectionClass $reflection, string $name)
    {
        if (!$reflection->hasProperty($name)) {
            return '\'\'';
        }

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
     * @param $xmlName
     * @return XmlNode|void
     */
    private function xml2Struct($xmlName)
    {
        $path = ROOT_PATH . self::$dirXml . self::uncamelize($xmlName) . '.xml';

        $fh = fopen($path,'r') or die($php_errormsg);
        $simple = fread($fh, filesize($path));
        fclose($fh) or die($php_errormsg);

        $p = xml_parser_create();
        $vals = [];
        xml_parse_into_struct($p, $simple, $vals, $index);
        xml_parser_free($p);

        $obj = new ArrayObject( $vals );
        $this->xmlIterator = $obj->getIterator();

        try {
            $root = null;
            while( $this->xmlIterator->valid() )
            {
                $data = $this->xmlIterator->current();
                //var_dump($data);

                $node = $this->xml2StructByRecursion($root, $data);
                if (empty($root)) {
                    $root = $node;
                }

                $this->xmlIterator->next();
            }

            return $root;
        } finally {
            $this->xmlIterator = null;
        }
    }

    /**
     * @param $parent
     * @param $data
     * @return XmlNode
     */
    private function xml2StructByRecursion($parent, $data): XmlNode
    {
        if (isset($data['value'])) {
            $data['value'] = $this->mutiLine2Line($data['value']);
        } else {
            $data['value'] = '';
        }

        switch ($data['type']) {
            case 'open':
                {
                    $node = new XmlNode($data['attributes'] ?? [], $data['tag']);

                    if (!empty($data['value'])) {
                        $node->addChind($data['value']);
                    }

                    if (!empty($parent)) {
                        $parent->addChind($node);
                    }

                    //下一个迭代器
                    $this->xmlIterator->next();

                    //遍历自己的字节点，直到
                    while ($this->xmlIterator->valid()) {
                        $nextdata = $this->xmlIterator->current();
                        if ($nextdata['type'] === 'close') {
                            break;
                        }
                        $this->xml2StructByRecursion($node, $nextdata);
                        $this->xmlIterator->next();
                    }

                    if (empty($parent)) {
                        return $node;
                    }
                }break;
            case 'cdata':
                {
                    if (!empty($data['value'])) {
                        $parent->addChind($data['value']);
                    }
                }break;
            case 'complete':
                {
                    $node = new XmlNode($data['attributes'] ?? [], $data['tag']);

                    if (!empty($data['value'])) {
                        $node->addChind($data['value']);
                    }

                    if (!empty($parent)) {
                        $parent->addChind($node);
                    }
                }break;
        }

        return $parent;
    }

    private function mutiLine2Line($string) {
        $arr = preg_split('/(\n|\r)/', $string, -1, PREG_SPLIT_NO_EMPTY);
        $rtString = '';
        foreach ($arr as $line) {
            $line = trim(str_replace(["\n", "\r"], '', $line));
            if (!empty($line)) {
                $rtString .= ' ' . $line;
            }
        }

        return trim($rtString);
    }


    /**
     * 解析xml
     * @param $xmlName
     * @return array
     * @throws OrangeBatisException
     */
    private function parseXml($xmlName): array
    {
        $root = $this->xml2Struct($xmlName);
        $namespace = $root->getNameSpace();
        $this->setNameSpace($namespace);

        $funcContents = [];
        $varContents = [];

        /**
         * @var XmlNode $xmlContent
         */
        foreach ($root->getChildren() as $xmlContent) {
            $id = $xmlContent->getId();
            if (count($xmlContent->getChildren()) > 0) {

                $children = $xmlContent->getChildren();

                foreach ($children as $child) {

                    if (is_string($child)) {
                        $xmlContent->addLayer(new StringNode($child));
                    } else {

                        switch ($child->getTag()) {
                            case 'foreach':
                                $xmlContent->addLayer(new ForeachNode($child->getAttribute(null), $child->getChildren(0)));
                                $xmlContent->addExcludeParam($child->getAttribute('collection'));
                                break;
                            case 'variate':
                                $xmlContent->addLayer(new VariateNode($child->getAttribute(null)));
                                $xmlContent->addExcludeParam($child->getAttribute('collection'));
                                break;
                            case 'choose':
                                $choose = new ChooseNode();
                                $excludeParam = $child->getAttribute('exclude');
                                $xmlContent->addExcludeParam($excludeParam);

                                foreach ($child->getChildren() as $subchild) {

                                    switch ($subchild->getTag()) {
                                        case 'when':
                                            $compare = new CompareNode($subchild->getAttribute('test'), $subchild->getAttribute('include'), $subchild->getChildren(0));
                                            $choose->addWhen($compare);
                                            break;
                                        case 'otherwise':
                                            $choose->setOtherwise($subchild->getChildren(0));
                                            break;
                                    }
                                }
                                $xmlContent->addLayer($choose);
                                break;
                            case 'sql';
                                //变量
                                $xmlContent->addLayer(new SqlNode($child->getId()));
                            default:
                                break;
                        }
                    }
                }
            }
            switch ($xmlContent->getTag()) {
                case 'select':
                case 'update':
                case 'delete':
                case 'insert':
                    $funcContents[$id] = $xmlContent;
                    break;
                case 'sql':
                    $varContents[$id] = $xmlContent;
                default:
                    break;
            }
        }

        return [$namespace, $funcContents, $varContents];
    }

    /**
     * 生成ORM操作方法
     * @param ReflectionMethod $method
     * @param XmlNode $xmlNode
     * @return string
     * @throws OrangeBatisException
     * @throws ReflectionException
     */
    private function generateMethod(ReflectionMethod $method, XmlNode $xmlNode): string
    {
        $function = 'public function ' . $method->getName() . '(';

        $reflectionParameters = $method->getParameters();
        $paramList = [];
        $paramArr = [];
        foreach ($reflectionParameters as $parameter) {
            $paramStr = '';
            $param = ['name' => trim($parameter->getName()), 'type' => ''];

            $type = $parameter->getType();
            if (!is_null($type)) {
                $className = $this->getClassShortName($type);
                $paramStr .= $className . ' ';

                $param['type'] = $className;
            }
            $paramStr .= '$' . $parameter->getName();

            $paramArr[] = $paramStr;
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
     * @param ReflectionMethod $method
     * @param array $paramList
     * @param XmlNode $xmlNode
     * @return string
     * @throws OrangeBatisException|ReflectionException
     */
    private function generateMethodContent(ReflectionMethod $method, array $paramList, XmlNode $xmlNode): string
    {
        switch ($xmlNode->getTag()) {
            case 'insert':
                return $this->insertMethodContent($method, $paramList, $xmlNode);
            case 'update':
            case 'delete':
                return $this->updateMethodContent($method, $paramList, $xmlNode);
            case 'select':
                return $this->selectMethodContent($method, $paramList, $xmlNode);
            default:
                return '';
        }
    }

    /**
     * 生成插入方法体
     * @param ReflectionMethod $method
     * @param array $paramList
     * @param XmlNode $xmlNode
     * @return string
     * @throws OrangeBatisException
     */
    private function insertMethodContent(ReflectionMethod $method, array $paramList, XmlNode $xmlNode): string
    {
        $content = '';

        $transaction = false;
        if ($xmlNode->getTransaction()) {
            $transaction = true;
            $content .= '$this->begin();' . PHP_EOL;
        }

        $layers = $xmlNode->getLayer();

        /**
         * @var BaseNode $layer
         */
        foreach ($layers as $idx => $layer) {
            $content .= $layer->getString($idx);
        }

        $paramList = $xmlNode->excludeParam($paramList);

        foreach ($paramList as $param) {

            if (in_array($param['type'], ['int', 'string', 'float', 'double', 'bool', 'boolean'])) {
                //if (strpos($xmlNode->getSql(), ':' . $param['name']) !== false) {
                $content .= '$this->bindParam(\'' . $param['name'] . '\', $' . $param['name'] . ');' . PHP_EOL;
                //}
            }/* elseif (in_array($param['type'], ['array', 'object'])) {

                    $content .= 'foreach ($' . $param['name'] . ' as $key => $value) {' . PHP_EOL;
                    $content .= 'if (strpos($sql, \':\' . $key) !== false) {' . PHP_EOL;
                    $content .= '$this->bindParam($key, $value);' . PHP_EOL;
                    $content .= '}' . PHP_EOL;
                    $content .= '}' . PHP_EOL;
                } else {

                    $reflection = $this->getReflectionByClassName($param['type']);
                    $parentClass = $reflection->getParentClass();
                    if (!empty($parentClass) && $parentClass->getName() == $this->classNameSpace('BaseBean')) {

                        $content .= '$' . $param['name'] . ' = $' . $param['name'] . '->genDataMap();' . PHP_EOL;

                        $content .= 'foreach ($' . $param['name'] . ' as $key => $value) {' . PHP_EOL;
                        $content .= 'if (strpos($sql, \':\' . $key) !== false) {' . PHP_EOL;
                        $content .= '$this->bindParam($key, $value);' . PHP_EOL;
                        $content .= '}' . PHP_EOL;
                        $content .= '}' . PHP_EOL;
                    }
                }*/
        }


        if ($transaction) {
            $content .= '$rt = $this->execute($sql);' . PHP_EOL;
            $content .= 'return $this->commit()? $rt : ';

            $methodReturnName = '';
            if (!empty($method->getReturnType())) {
                $methodReturnName = $method->getReturnType()->getName();
            }
            switch ($methodReturnName) {
                case 'int':$content .= '0;';break;
                case 'float':$content .= '0.0;';break;
                case 'array':$content .= '[];';break;
                case '':case 'string':$content .= '\'\';';break;
                default:$content .= 'null;';break;
            }
        } else {
            $content .= 'return $this->execute($sql);' . PHP_EOL;
        }

        return $content;
    }

    /**
     * * 生成修改方法体
     * @param ReflectionMethod $method
     * @param array $paramList
     * @param XmlNode $xmlNode
     * @return string
     * @throws OrangeBatisException
     */
    private function updateMethodContent(ReflectionMethod $method, array $paramList, XmlNode $xmlNode): string
    {
        $content = '';

        $transaction = false;
        if ($xmlNode->getTransaction()) {
            $transaction = true;
            $content .= '$this->begin();' . PHP_EOL;
        }

        $layers = $xmlNode->getLayer();

        /**
         * @var BaseNode $layer
         */
        foreach ($layers as $idx => $layer) {
            $content .= $layer->getString($idx);
        }

        $paramList = $xmlNode->excludeParam($paramList);
        foreach ($paramList as $param) {

            if (in_array($param['type'], ['int', 'string', 'float', 'double', 'bool', 'boolean'])) {
                //if (strpos($xmlNode->getSql(), ':' . $param['name']) !== false) {
                $content .= '$this->bindParam(\'' . $param['name'] . '\', $' . $param['name'] . ');' . PHP_EOL;
                //}
            }/* elseif (in_array($param['type'], ['array', 'object'])) {
                    $content .= 'foreach ($' . $param['name'] . ' as $key => $value) {' . PHP_EOL;
                    $content .= 'if (strpos($sql, \':\' . $key) !== false) {' . PHP_EOL;
                    $content .= '$this->bindParam($key, $value);' . PHP_EOL;
                    $content .= '}' . PHP_EOL;
                    $content .= '}' . PHP_EOL;
                } else {

                    $reflection = $this->getReflectionByClassName($param['type']);
                    $parentClass = $reflection->getParentClass();
                    if (!empty($parentClass) && $parentClass->getName() == $this->classNameSpace('BaseBean')) {

                        $content .= '$' . $param['name'] . ' = $' . $param['name'] . '->genDataMap();' . PHP_EOL;

                        $content .= 'foreach ($' . $param['name'] . ' as $key => $value) {' . PHP_EOL;
                        $content .= 'if (strpos($sql, \':\' . $key) !== false) {' . PHP_EOL;
                        $content .= '$this->bindParam($key, $value);' . PHP_EOL;
                        $content .= '}' . PHP_EOL;
                        $content .= '}' . PHP_EOL;

                    }
                }*/
        }

        if ($transaction) {
            $content .= '$rt = $this->execute($sql);' . PHP_EOL;
            $content .= 'return $this->commit()? $rt : ';

            $methodReturnName = '';
            if (!empty($method->getReturnType())) {
                $methodReturnName = $method->getReturnType()->getName();
            }
            switch ($methodReturnName) {
                case 'int':$content .= '0;';break;
                case 'float':$content .= '0.0;';break;
                case 'array':$content .= '[];';break;
                case '':case 'string':$content .= '\'\';';break;
                default:$content .= 'null;';break;
            }
        } else {
            $content .= 'return $this->execute($sql);' . PHP_EOL;
        }

        return $content;
    }

    /**
     * * 生成查询方法体
     * @param ReflectionMethod $method
     * @param array $paramList
     * @param XmlNode $xmlNode
     * @return string
     * @throws OrangeBatisException
     * @throws ReflectionException
     */
    private function selectMethodContent(ReflectionMethod $method, array $paramList, XmlNode $xmlNode): string
    {
        $content = '';
        $transaction = false;
        if ($xmlNode->getTransaction()) {
            $transaction = true;
        }

        $layers = $xmlNode->getLayer();

        /**
         * @var BaseNode $layer
         */
        foreach ($layers as $idx => $layer) {
            $content .= $layer->getString($idx);
        }

        $paramList = $xmlNode->excludeParam($paramList);
        foreach ($paramList as $param) {
            if (in_array($param['type'], ['int', 'string', 'float', 'double', 'bool', 'boolean'])) {
                $content .= '$this->bindParam(\'' . $param['name'] . '\', $' . $param['name'] . ');' . PHP_EOL;
            } elseif (in_array($param['type'], ['array', 'object'])) {
                $content .= '$where = [];' . PHP_EOL;
                $content .= 'foreach ($' . $param['name'] . ' as $key => $value) {' . PHP_EOL;
                $content .= '$fieldKey = $this->filterKey($key);' . PHP_EOL;
                $content .= '$this->bindParam($fieldKey, $value);' . PHP_EOL;
                $content .= '$where[] = \'`\' . $key . \'`\' . \'=:\' . $fieldKey;' . PHP_EOL;
                $content .= '}' . PHP_EOL;

                $content .= '$sql .= join(\' and \', $where);' . PHP_EOL;
            } else {
                $content .= '$whereData = $this->getSelectWhere($' . $param['name'] . ');' . PHP_EOL;
                $content .= '$where = [];' . PHP_EOL;
                $content .= 'foreach ($whereData as $value) {' . PHP_EOL;
                $content .= '$this->bindParam($value[\'fieldKey\'], $value[\'value\']);' . PHP_EOL;
                $content .= '$where[] = \'`\' . $value[\'field\'] . \'`\' . \'=:\' . $value[\'fieldKey\'];' . PHP_EOL;
                $content .= '}' . PHP_EOL;
                $content .= '$sql .= join(\' and \', $where);' . PHP_EOL;
            }
        }

        $methodReturnName = '';
        if (!empty($method->getReturnType())) {
            $methodReturnName = $method->getReturnType()->getName();
        }

        $rtName = '$rt';
        if ($transaction) {
            switch ($methodReturnName) {
                case 'int':
                    $content .= $rtName . ' = intval($this->getOne($sql));' . PHP_EOL;
                    break;
                case 'string':
                    $content .= $rtName . ' = $this->getOne($sql);' . PHP_EOL;
                    break;
                case 'float':
                    $content .= $rtName . ' = floatval($this->getOne($sql));' . PHP_EOL;
                    break;
                default:
                    $content .= $this->setValue($xmlNode, $method->getReturnType(), $rtName);
                    break;
            }

            $content .= 'return $this->commit()? '.$rtName.' : ';

            switch ($methodReturnName) {
                case 'int':$content .= '0;';break;
                case 'float':$content .= '0.0;';break;
                case 'array':$content .= '[];';break;
                case '':case 'string':$content .= '\'\';';break;
                default:$content .= 'null;';break;
            }
        } else {
            switch ($methodReturnName) {
                case 'int':
                    $content .= 'return intval($this->getOne($sql));' . PHP_EOL;
                    break;
                case 'string':
                    $content .= 'return $this->getOne($sql);' . PHP_EOL;
                    break;
                case 'float':
                    $content .= 'return floatval($this->getOne($sql));' . PHP_EOL;
                    break;
                default:
                    $content .= $this->setValue($xmlNode, $method->getReturnType(), $rtName);
                    $content .= 'return ' . $rtName . ';' . PHP_EOL;
                    break;
            }
        }

        return $content;
    }

    //--------------------------------------------------------------------------------------------------------------

    /**
     * @param $path
     * @param $xml
     * @param $idao
     * @return bool
     * @throws OrangeBatisException
     */
    private function checkFileTime($path, $xml, $idao): bool
    {
        $thisTime = filemtime(__FILE__);
        $pathTime = file_exists($path) ? filemtime($path) : 0;
        $xmlTime = file_exists($xml) ? filemtime($xml) : 0;
        if (empty($xmlTime)) {
            throw new OrangeBatisException('xml "'.$xml.'" is not found!');
        }

        $idaoTime = filemtime($idao);
        if (empty($idaoTime)) {
            throw new OrangeBatisException('iDao "'.$idao.'" is not found!');
        }

        return ($pathTime >= max($thisTime, $xmlTime, $idaoTime));
    }

    /**
     * @param $interFaceName
     * @return string
     * @throws OrangeBatisException
     * @throws ReflectionException
     */
    public function getMapper($interFaceName): string
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
        $idao = ROOT_PATH . 'DB/DAO/' . $interFaceName . '.php';

        if (self::$forceCompile || !file_exists($path) || !self::checkFileTime($path, $xml, $idao)) {
            //强制编译 或者 编译文件不存在
            $this->generatedClass($interFaceName);
        }

        return $className;
    }
}
