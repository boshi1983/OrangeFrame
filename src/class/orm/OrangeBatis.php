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

class XmlNode
{
    /**
     * @var string
     */
    private $id = '';

    /**
     * @var string
     */
    private $tag = '';

    /**
     * @var string
     */
    private $resultType = '';

    /**
     * @var false
     */
    private $transaction = false;

    /**
     * @var array
     */
    private $func = [];

    /**
     * @var array
     */
    private $variate = [];

    /**
     * @var string
     */
    private $sql = '';

    /**
     * @var XmlNode
     */
    private $parent = null;

    /**
     * @var array
     */
    private $children = [];

    /**
     * @var array
     */
    private $attributes = [];

    /**
     * XmlNode constructor.
     * @param string $id
     * @param string $tag
     * @param string $resultType
     * @param false $transaction
     */
    public function __construct(string $id, string $tag, string $resultType = '', $transaction = false)
    {
        $this->id = $id;
        $this->tag = strtolower($tag);
        $this->resultType = $resultType;
        $this->transaction = $transaction;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * @param mixed $tag
     */
    public function setTag($tag): void
    {
        $this->tag = $tag;
    }

    /**
     * @return mixed
     */
    public function getResultType()
    {
        return $this->resultType;
    }

    /**
     * @param mixed $resultType
     */
    public function setResultType($resultType): void
    {
        $this->resultType = $resultType;
    }

    /**
     * @return mixed
     */
    public function getTransaction()
    {
        return $this->transaction;
    }

    /**
     * @param mixed $transaction
     */
    public function setTransaction($transaction): void
    {
        $this->transaction = $transaction;
    }

    /**
     * @return mixed
     */
    public function getFunc()
    {
        return $this->func;
    }

    /**
     * @param mixed $func
     */
    public function setFunc($func): void
    {
        $this->func = $func;
    }

    /**
     * @return array
     */
    public function getVariate(): array
    {
        return $this->variate;
    }

    /**
     * @param array $variate
     */
    public function setVariate(array $variate): void
    {
        $this->variate = $variate;
    }

    /**
     * @return mixed
     */
    public function getSql()
    {
        return $this->sql;
    }

    /**
     * @param mixed $sql
     */
    public function setSql($sql): void
    {
        $this->sql = $sql;
    }

    /**
     * @return XmlNode
     */
    public function getParent(): ?XmlNode
    {
        return $this->parent;
    }

    /**
     * @param XmlNode $parent
     */
    public function setParent(?XmlNode $parent): void
    {
        $this->parent = $parent;
    }

    public function addChind($node): void
    {
        $this->children[] = $node;
    }

    public function getChildren():array
    {
        return $this->children;
    }

    public function setAttribute($attributes):void
    {
        $this->attributes = $attributes;
    }

    public function getAttribute($key)
    {
        if (empty($key)) {
            return $this->attributes;
        }

        return $this->attributes[strtoupper($key)] ?? '';
    }

    public function getNameSpace()
    {
        return $this->attributes['NAMESPACE'] ?? '';
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
    private function getClassShortName(ReflectionType $type)
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
     * @throws OrangeBatisException
     */
    private function generatedClass(string $interFaceName)
    {
        $xmlName = substr($interFaceName, 1, strlen($interFaceName) - 4);
        list($namespace, $xmlContent) = $this->parseXml($xmlName);

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

        $class = '';
        if (!empty($namespace)) {
            $class .= 'namespace ' . $namespace . '{' . PHP_EOL;
        }
        $class .= 'final class ' . $xmlName . 'Proxy extends BaseBatisProxy implements ' . $interFaceName . ' {' . PHP_EOL;
        $class .= 'const VERSION = ' . microtime(true) . ';' . PHP_EOL;
        $class .= join(PHP_EOL, $functionList) . PHP_EOL;

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
     * @param ReflectionNamedType|ReflectionType $returnType
     * @param string $rtName
     * @return string
     * @throws OrangeBatisException|ReflectionException
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
     * @return string
     * @throws OrangeBatisException|ReflectionException
     */
    private function row2Object(string $class, string $value, string $returnName)
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
     * @throws ReflectionException
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

    private function xmlIntoStruct($xmlName)
    {
        $path = ROOT_PATH . self::$dirXml . self::uncamelize($xmlName) . '.xml';

        $fh = fopen($path, 'r') or die($php_errormsg);
        $simple = fread($fh, filesize($path));
        fclose($fh) or die($php_errormsg);

        $p = xml_parser_create();
        xml_parse_into_struct($p, $simple, $vals, $index);
        xml_parser_free($p);

        $root = null;
        $curnode = null;

        foreach ($vals as $v) {

            if (isset($v['value'])) {
                $v['value'] = trim(str_replace("\n", ' ', $v['value']));
            }

            switch ($v['type']) {
                case 'open':
                    $attributes = $v['attributes'];
                    $node = new XmlNode($attributes['ID'] ?? '', $v['tag'], $attributes['RESULTTYPE'] ?? '', $attributes['TRANSACTION'] ?? '');
                    $node->setAttribute($attributes);
                    $node->setParent($curnode);

                    $value = trim($v['value'] ?? '');
                    if (!empty($value)) {
                        $node->addChind($value);
                    }

                    $curnode = $node;
                    if (is_null($root)) {
                        $root = $curnode;
                    } else {
                        $root->addChind($curnode);
                    }
                    break;
                case 'cdata':
                    $value = trim($v['value'] ?? '');
                    if (!empty($value)) {
                        $curnode->addChind($value);
                    }
                    break;
                case 'close':
                    $curnode = $curnode->getParent();
                    break;
                case 'complete':
                    $attributes = $v['attributes'];
                    $node = new XmlNode($attributes['ID'] ?? '', $v['tag'], $attributes['RESULTTYPE'] ?? '', $attributes['TRANSCATION'] ?? '');
                    $node->setAttribute($attributes);

                    $value = trim($v['value'] ?? '');
                    if (!empty($value)) {
                        $node->setSql($value);
                    }

                    $curnode->addChind($node);
                    break;
                default:
                    echo $v['type'] . '<br>';
            }
        }

        return $root;
    }

    /**
     * 解析xml
     * @param $xmlName
     * @return array
     * @throws OrangeBatisException
     */
    private function parseXml($xmlName)
    {
        $root = $this->xmlIntoStruct($xmlName);
        $namespace = $root->getNameSpace();
        $this->setNameSpace($namespace);

        $xmlContents = [];
        foreach ($root->getChildren() as $xmlContent) {
            $id = $xmlContent->getId();
            if (count($xmlContent->getChildren()) > 0) {

                $children = $xmlContent->getChildren();
                $sql = '';
                $funcs = [];
                $vars = [];
                foreach ($children as $idx => $child) {

                    if (is_string($child)) {
                        $sql .= $child;
                    } else {

                        $func = '';
                        $var = '';

                        switch ($child->getTag()) {
                            case 'foreach':
                                $sql .= ' \' . $param' . $idx . ' . \' ';

                                $func = '$param' . $idx . ' = $this->foreach(';

                                $collection = $child->getAttribute('collection');
                                $item = $child->getAttribute('item');
                                $open = $child->getAttribute('open');
                                $separator = $child->getAttribute('separator');
                                $close = $child->getAttribute('close');

                                $content = $child->getSql();

                                $func .= $collection . ',\'' . $item . '\',\'' . $content . '\',\'' . $open . '\',\'' . $separator . '\',\'' . $close . '\'';

                                $func .= ');' . PHP_EOL;

                                break;
                            case 'variate':
                                $collection = $child->getAttribute('collection');
                                $var = ['name' => $child->getAttribute('name'), 'type' => $child->getAttribute('type')];
                                $sql .= ' \' . ' . $var['name'] . ' . \' ';
                                $func = 'list($fields, $values, $datas, $length) = $this->getInsertInfo(' . $collection . ');' . PHP_EOL;
                                break;
                            default:
                                break;
                        }

                        if (!empty($func)) {
                            $funcs[] = $func;
                        }

                        if (!empty($var)) {
                            $vars[] = $var;
                        }
                    }
                }

                $xmlContent->setFunc($funcs);
                $xmlContent->setVariate($vars);
                $xmlContent->setSql($sql);
            }
            $xmlContents[$id] = $xmlContent;
        }

        return [$namespace, $xmlContents];
    }

    /**
     * 生成ORM操作方法
     * @param ReflectionMethod $method
     * @param XmlNode $xmlNode
     * @return string
     * @throws OrangeBatisException
     * @throws ReflectionException
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
     * @param ReflectionMethod $method
     * @param array $paramList
     * @param XmlNode $xmlNode
     * @return string
     * @throws OrangeBatisException|ReflectionException
     */
    private function generateMethodContent(ReflectionMethod $method, array $paramList, XmlNode $xmlNode)
    {
        $content = null;
        switch ($xmlNode->getTag()) {
            case 'insert':
                $content = $this->insertMethodContent($method, $paramList, $xmlNode);
                break;
            case 'update':
            case 'delete':
                $content = $this->updateMethodContent($method, $paramList, $xmlNode);
                break;
            default://'select'
                $content = $this->selectMethodContent($method, $paramList, $xmlNode);
                break;
        }

        return $content;
    }

    /**
     * 生成插入方法体
     * @param ReflectionMethod $method
     * @param array $paramList
     * @param XmlNode $xmlNode
     * @return string
     * @throws OrangeBatisException
     */
    private function insertMethodContent(ReflectionMethod $method, array $paramList, XmlNode $xmlNode)
    {
        $content = '';

        $transaction = false;
        if ($xmlNode->getTransaction()) {
            $transaction = true;
            $content .= '$this->begin();' . PHP_EOL;
        }

        $front = '';
        $behind = '';

        foreach ($paramList as $param) {

            if (in_array($param['type'], ['int', 'string', 'float', 'double', 'bool', 'boolean'])) {
                if (strpos($xmlNode->getSql(), ':' . $param['name']) !== false) {
                    $front .= '$this->bindParam(\'' . $param['name'] . '\', $' . $param['name'] . ');' . PHP_EOL;
                }
            } elseif (in_array($param['type'], ['array', 'object'])) {

                $func = array_unique($xmlNode->getFunc());
                if (is_array($func)) {
                    $front .= join('', $func);
                }

                if (empty($func)) {
                    $behind .= 'foreach ($' . $param['name'] . ' as $key => $value) {' . PHP_EOL;
                    $behind .= 'if (strpos($sql, \':\' . $key) !== false) {' . PHP_EOL;
                    $behind .= '$this->bindParam($key, $value);' . PHP_EOL;
                    $behind .= '}' . PHP_EOL;
                    $behind .= '}' . PHP_EOL;
                } else {
                    $front .= 'foreach ($datas as $key => $value) {' . PHP_EOL;
                    $front .= '$this->bindParam($key, $value);' . PHP_EOL;
                    $front .= '}' . PHP_EOL;
                }
            } else {

                $reflection = $this->getReflectionByClassName($param['type']);
                $parentClass = $reflection->getParentClass();
                if (!empty($parentClass) && $parentClass->getName() == $this->classNameSpace('BaseBean')) {

                    $front .= '$' . $param['name'] . ' = $' . $param['name'] . '->genDataMap();' . PHP_EOL;

                    $func = array_unique($xmlNode->getFunc());
                    if (is_array($func)) {
                        $front .= join('', $func);
                    }

                    if (empty($func)) {
                        $behind .= 'foreach ($' . $param['name'] . ' as $key => $value) {' . PHP_EOL;
                        $behind .= 'if (strpos($sql, \':\' . $key) !== false) {' . PHP_EOL;
                        $behind .= '$this->bindParam($key, $value);' . PHP_EOL;
                        $behind .= '}' . PHP_EOL;
                        $behind .= '}' . PHP_EOL;
                    } else {
                        $front .= 'foreach ($datas as $key => $value) {' . PHP_EOL;
                        $front .= '$this->bindParam($key, $value);' . PHP_EOL;
                        $front .= '}' . PHP_EOL;
                    }

                } else {

                    $vars = $xmlNode->getVariate();
                    foreach ($vars as $var) {
                        $front .= $var['name'] . ' = [];' . PHP_EOL;
                    }

                    $methodList = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
                    foreach ($methodList as $method) {
                        $methodName = $method->getName();
                        if (strpos($methodName, 'get') !== 0)
                            continue;

                        $varname = self::uncamelize(substr($methodName, 3));

                        $front .= 'if (!is_null(' . '$' . $param['name'] . '->' . $methodName . '())) {' . PHP_EOL;
                        $front .= '$this->bindParam(\'' . $varname . '\', ' . '$' . $param['name'] . '->' . $methodName . '());' . PHP_EOL;

                        foreach ($vars as $var) {
                            switch ($var['type']) {
                                case 'field':
                                    $front .= $var['name'] . '[] = \'' . $varname . '\';' . PHP_EOL;
                                    break;
                                case 'value':
                                    $front .= $var['name'] . '[] = \':' . $varname . '\';' . PHP_EOL;
                                    break;
                            }
                        }

                        $front .= '}' . PHP_EOL;
                    }

                    foreach ($vars as $var) {
                        switch ($var['type']) {
                            case 'field':
                                $front .= $var['name'] . ' = join(\',\', ' . $var['name'] . ');' . PHP_EOL;
                                break;
                            case 'value':
                                $front .= $var['name'] . ' = \'(\' . join(\',\', ' . $var['name'] . ') . \')\';' . PHP_EOL;
                                break;
                        }
                    }

                    $front .= PHP_EOL;
                }
            }
        }

        $content .= $front;
        $content .= '$sql = \'' . $xmlNode->getSql() . '\';' . PHP_EOL;
        $content .= $behind;

        $content .= '$this->execute($sql);' . PHP_EOL;

        if ($transaction) {
            $rtName = '$rt';
            $content .= $rtName . ' = $this->getLastInsID();' . PHP_EOL;
            $content .= 'return $this->commit()? ' . $rtName . ' : ';

            $methodReturnName = '';
            if (!empty($method->getReturnType())) {
                $methodReturnName = $method->getReturnType()->getName();
            }
            switch ($methodReturnName) {
                case 'int':
                    $content .= '0;';
                    break;
                case 'float':
                    $content .= '0.0;';
                    break;
                case 'array':
                    $content .= '[];';
                    break;
                case '':
                case 'string':
                    $content .= '\'\';';
                    break;
                default:
                    $content .= 'null;';
                    break;
            }
        } else {
            $content .= 'return $this->getLastInsID();' . PHP_EOL;
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
    private function updateMethodContent(ReflectionMethod $method, array $paramList, XmlNode $xmlNode)
    {
        $content = '';

        $transaction = false;
        if ($xmlNode->getTransaction()) {
            $transaction = true;
            $content .= '$this->begin();' . PHP_EOL;
        }

        $front = '';
        $behind = '';

        foreach ($paramList as $param) {

            if (in_array($param['type'], ['int', 'string', 'float', 'double', 'bool', 'boolean'])) {
                if (strpos($xmlNode->getSql(), ':' . $param['name']) !== false) {
                    $front .= '$this->bindParam(\'' . $param['name'] . '\', $' . $param['name'] . ');' . PHP_EOL;
                }
            } elseif (in_array($param['type'], ['array', 'object'])) {

                $func = array_unique($xmlNode->getFunc());
                if (is_array($func)) {
                    $front .= join('', $func);
                }

                if (empty($func)) {
                    $behind .= 'foreach ($' . $param['name'] . ' as $key => $value) {' . PHP_EOL;
                    $behind .= 'if (strpos($sql, \':\' . $key) !== false) {' . PHP_EOL;
                    $behind .= '$this->bindParam($key, $value);' . PHP_EOL;
                    $behind .= '}' . PHP_EOL;
                    $behind .= '}' . PHP_EOL;
                } else {

                    $vars = $xmlNode->getVariate();
                    foreach ($vars as $var) {
                        $front .= $var['name'] . ' = [];' . PHP_EOL;
                    }

                    $front .= 'foreach ($datas as $key => $value) {' . PHP_EOL;
                    $front .= '$this->bindParam($key, $value);' . PHP_EOL;

                    foreach ($vars as $var) {
                        switch ($var['type']) {
                            case 'set':
                                $front .= $var['name'] . '[] = $key . \'=:\' . $key;' . PHP_EOL;
                                break;
                        }
                    }

                    $front .= '}' . PHP_EOL;

                    foreach ($vars as $var) {
                        switch ($var['type']) {
                            case 'set':
                                $front .= $var['name'] . ' = join(\',\', ' . $var['name'] . ');' . PHP_EOL;
                                break;
                        }
                    }
                }
            } else {

                $reflection = $this->getReflectionByClassName($param['type']);
                $parentClass = $reflection->getParentClass();
                if (!empty($parentClass) && $parentClass->getName() == $this->classNameSpace('BaseBean')) {

                    $front .= '$' . $param['name'] . ' = $' . $param['name'] . '->genDataMap();' . PHP_EOL;

                    $func = array_unique($xmlNode->getFunc());
                    if (is_array($func)) {
                        $front .= join('', $func);
                    }

                    if (empty($func)) {
                        $behind .= 'foreach ($' . $param['name'] . ' as $key => $value) {' . PHP_EOL;
                        $behind .= 'if (strpos($sql, \':\' . $key) !== false) {' . PHP_EOL;
                        $behind .= '$this->bindParam($key, $value);' . PHP_EOL;
                        $behind .= '}' . PHP_EOL;
                        $behind .= '}' . PHP_EOL;
                    } else {

                        $vars = $xmlNode->getVariate();
                        foreach ($vars as $var) {
                            $front .= $var['name'] . ' = [];' . PHP_EOL;
                        }

                        $front .= 'foreach ($datas as $key => $value) {' . PHP_EOL;
                        $front .= '$this->bindParam($key, $value);' . PHP_EOL;

                        foreach ($vars as $var) {
                            switch ($var['type']) {
                                case 'set':
                                    $front .= $var['name'] . '[] = $key . \'=:\' . $key;' . PHP_EOL;
                                    break;
                            }
                        }

                        $front .= '}' . PHP_EOL;

                        foreach ($vars as $var) {
                            switch ($var['type']) {
                                case 'set':
                                    $front .= $var['name'] . ' = join(\',\', ' . $var['name'] . ');' . PHP_EOL;
                                    break;
                            }
                        }

                    }

                } else {

                    $vars = $xmlNode->getVariate();
                    foreach ($vars as $var) {
                        $front .= $var['name'] . ' = [];' . PHP_EOL;
                    }

                    $methodList = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
                    foreach ($methodList as $method) {
                        $methodName = $method->getName();
                        if (strpos($methodName, 'get') !== 0)
                            continue;

                        $varname = self::uncamelize(substr($methodName, 3));

                        $front .= 'if (!is_null(' . '$' . $param['name'] . '->' . $methodName . '())) {' . PHP_EOL;
                        $front .= '$this->bindParam(\'' . $varname . '\', ' . '$' . $param['name'] . '->' . $methodName . '());' . PHP_EOL;

                        foreach ($vars as $var) {
                            switch ($var['type']) {
                                case 'field':
                                    $front .= $var['name'] . '[] = \'' . $varname . '\';' . PHP_EOL;
                                    break;
                                case 'value':
                                    $front .= $var['name'] . '[] = \':' . $varname . '\';' . PHP_EOL;
                                    break;
                            }
                        }

                        $front .= '}' . PHP_EOL;
                    }

                    foreach ($vars as $var) {
                        switch ($var['type']) {
                            case 'field':
                                $front .= $var['name'] . ' = join(\',\', ' . $var['name'] . ');' . PHP_EOL;
                                break;
                            case 'value':
                                $front .= $var['name'] . ' = \'(\' . join(\',\', ' . $var['name'] . ') . \')\';' . PHP_EOL;
                                break;
                        }
                    }

                    $front .= PHP_EOL;
                }
            }
        }

        $content .= $front;
        $content .= '$sql = \'' . $xmlNode->getSql() . '\';' . PHP_EOL;
        $content .= $behind;

        if ($transaction) {
            $rtName = '$rt';
            $content .= $rtName . ' = $this->execute($sql);' . PHP_EOL;
            $content .= 'return $this->commit()? ' . $rtName . ' : ';

            $methodReturnName = '';
            if (!empty($method->getReturnType())) {
                $methodReturnName = $method->getReturnType()->getName();
            }
            switch ($methodReturnName) {
                case 'int':
                    $content .= '0;';
                    break;
                case 'float':
                    $content .= '0.0;';
                    break;
                case 'array':
                    $content .= '[];';
                    break;
                case '':
                case 'string':
                    $content .= '\'\';';
                    break;
                default:
                    $content .= 'null;';
                    break;
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
    private function selectMethodContent(ReflectionMethod $method, array $paramList, XmlNode $xmlNode)
    {
        $content = '';
        $transaction = false;
        if ($xmlNode->getTransaction()) {
            $transaction = true;
        }

        $func = $xmlNode->getFunc();
        if (is_array($func)) {
            $content .= join('', $func);
        }
        $content .= '$sql = \'' . $xmlNode->getSql() . '\';' . PHP_EOL;

        foreach ($paramList as $param) {
            if (in_array($param['type'], ['int', 'string', 'float', 'double', 'bool', 'boolean'])) {
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
                    $content .= $rtName . ' = \'\' . $this->getOne($sql);' . PHP_EOL;
                    break;
                case 'float':
                    $content .= $rtName . ' = floatval($this->getOne($sql));' . PHP_EOL;
                    break;
                default:
                    $content .= $this->setValue($xmlNode, $method->getReturnType(), $rtName);
                    break;
            }

            $content .= 'return $this->commit()? ' . $rtName . ' : ';

            switch ($methodReturnName) {
                case 'int':
                    $content .= '0;';
                    break;
                case 'float':
                    $content .= '0.0;';
                    break;
                case 'array':
                    $content .= '[];';
                    break;
                case '':
                case 'string':
                    $content .= '\'\';';
                    break;
                default:
                    $content .= 'null;';
                    break;
            }
        } else {
            switch ($methodReturnName) {
                case 'int':
                    $content .= 'return intval($this->getOne($sql));' . PHP_EOL;
                    break;
                case 'string':
                    $content .= 'return \'\' . $this->getOne($sql);' . PHP_EOL;
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
    private function checkFileTime($path, $xml, $idao)
    {
        $thisTime = filemtime(__FILE__);
        $pathTime = file_exists($path) ? filemtime($path) : 0;
        $xmlTime = file_exists($xml) ? filemtime($xml) : 0;
        if (empty($xmlTime)) {
            throw new OrangeBatisException('xml "' . $xml . '" is not found!');
        }

        $idaoTime = filemtime($idao);
        if (empty($idaoTime)) {
            throw new OrangeBatisException('iDao "' . $idao . '" is not found!');
        }

        return ($pathTime >= max($thisTime, $xmlTime, $idaoTime));
    }

    /**
     * @param $interFaceName
     * @return object|string
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
        $idao = ROOT_PATH . 'DB/DAO/' . $interFaceName . '.php';

        if (self::$forceCompile || !file_exists($path) || !self::checkFileTime($path, $xml, $idao)) {
            //强制编译 或者 编译文件不存在
            $this->generatedClass($interFaceName);
        }

        return $className;
    }
}
