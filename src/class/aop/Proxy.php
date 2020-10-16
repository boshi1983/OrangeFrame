<?php

class Proxy
{
    //代理类序号
    static $proxyIndex = 0;

    //是否保存代理类文件
    static $saveClassFile = true;

    //代理名称
    static $classDict = [];

    /**
     *
     * @param $target
     * @param $handler
     * @return mixed
     */
    public static function newProxyInstance($target, $handler)
    {
        //获取$target的反射类
        $reflection = null;
        try {
            $reflection = new \ReflectionClass(get_class($target));
        } catch (\Exception $exception) {

        }

        //获取接口名列表
        $arrInterface = $reflection->getInterfaceNames();
        array_walk($arrInterface, function (&$item2, $key) {
            $arr = explode('\\', $item2);
            $item2 = end($arr);
        });

        //序列化接口类名
        sort($arrInterface);
        $interfaceHash = join('', $arrInterface);

        //查看是否已经生成动态代理类
        if (!isset(self::$classDict[$interfaceHash]) || empty(self::$classDict[$interfaceHash])) {
            //创建代理类名
            $className = '_PROXY_' . (self::$proxyIndex++);

            //生成动态代理类
            self::generatedClass($className, $arrInterface, $reflection);
            self::$classDict[$interfaceHash] = $className;
        } else {
            //获取代理类名
            $className = self::$classDict[$interfaceHash];
        }

        //代理类实例化
        return new $className($target, $handler);
    }

    /**
     * 动态生成代理类
     * @param $className
     * @param $arrInterface
     * @param $reflection
     */
    private static function generatedClass($className, $arrInterface, $reflection)
    {
        //设置代理类名，成员函数，析构函数
        $class = 'final class '.$className.' implements ' . join(',', $arrInterface) . ' {
private $target;
private $handler;
function __destruct(){$this->target = null;$this->handler=null;}';

        //构造函数中的初始化内容
        $methodConstruct = '';

        //获取接口类反射
        $ifClassArr = $reflection->getInterfaces();
        foreach ($ifClassArr as $interface) {
            //获取接口类中的接口函数
            $methods = $interface->getMethods();
            foreach ($methods as $k => $method) {
                //创建反射方法对象
                $class .= 'private static $m'.$k.';';

                //初始化反射对象
                $methodConstruct .= 'self::$m'.$k.' = $reflection->getMethod(\''.$method->getName().'\');';

                //生成代理类接口方法
                $class .= 'public function ' . $method->getName() . '(';

                //获取方法中的参数
                $params = $method->getParameters();

                //参数列表
                $arrParam = [];
                foreach ($params as $param) {

                    $strParam = '';
                    if ($param->isPassedByReference()) {
                        //参数为引用
                        $strParam .= '&';
                    }

                    //参数名
                    $strParam .= '$'.$param->getName();

                    //参数默认值设置
                    if ($param->isDefaultValueAvailable()) {
                        $strParam .= '=';
                        if ($param->isDefaultValueConstant()) {
                            $strParam .= $param->getDefaultValueConstantName();
                        } else {
                            $strParam .= var_export($param->getDefaultValue(), true);
                        }

                    }

                    $arrParam[] = $strParam;
                }
                //添加参数列表
                $class .= join(',', $arrParam);
                $class .= '){';

                //设置调用规则
                $class .= 'try {
    return $this->handler->invoke($this, $this->target, self::$m'.$k.', func_get_args());
} catch (Exception $e) {
    throw $e;
} finally {
}}';
            }
        }

        //添加构造方法。
        $class .= 'public function __construct($target, $handler){
$this->target = $target;$this->handler = $handler;$classname = get_class($target);
$reflection = null;
try {$reflection = new \ReflectionClass($classname);} 
catch (\Exception $exception) {}'.$methodConstruct.'}}';

        //保存动态代理类文件
        if (self::$saveClassFile) {
            file_put_contents(ROOT_PATH . 'ProxyTemp/' . $className . '.php', '<?php' . PHP_EOL . $class);
        }

        //加载动态代理类
        eval($class);
    }

    /**
     * 生成动态代理类文件开关
     * @param $bool
     */
    public static function saveGeneratedFiles($bool)
    {
        self::$saveClassFile = $bool;
    }
}