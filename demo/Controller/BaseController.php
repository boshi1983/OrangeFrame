<?php


abstract class BaseController
{
    protected $reflection;

    /**
     * @param ReflectionClass $reflection
     */
    public function __construct(ReflectionClass $reflection)
    {
        $this->reflection = $reflection;
    }


    /**
     * @param string $action
     * @param Request $request
     * @return mixed
     * @throws \ReflectionException
     */
    public function distribute(string $action, Request $request) {
        $reflectionMethod = $this->reflection->getMethod($action);
        $reflectionParameters = $reflectionMethod->getParameters();
        $countParam = count($reflectionParameters);

        $args = [];
        if ($countParam > 0) {
            //循环获取参数及类型
            foreach ($reflectionParameters as $parameter) {
                $args[$parameter->getName()] = $this->getArgs($request, $parameter);
            }
        }

        return $reflectionMethod->invokeArgs($this, $args);
    }

    /**
     * @return mixed
     */
    private function getArgs(Request $request, \ReflectionParameter $parameter) {

        //获取函数默认值
        try {
            //如果定义了默认值，则使用默认值。
            $default = $parameter->getDefaultValue();
        } catch (\Throwable $e) {
            $default = null;
        }

        //默认类型为字符串
        $type = 'string';
        if ($parameter->hasType()) {
            //如果定义了类型，则获取类型
            $type = $parameter->getType()->getName();
        } elseif (!is_null($default)) {
            //无法获取已定义类型，则用默认值推断类型
            $type = gettype($default);
        }

        $filter = '';
        switch ($type) {
            case 'int':
                $filter = 'intval';
                if (is_null($default))$default = 0;
                break;
            case 'float':
                $filter = 'floatval';
                if (is_null($default))$default = 0.0;
                break;
            case 'double':
                $filter = 'doubleval';
                if (is_null($default))$default = 0.0;
                break;
            case 'string':
                $filter = 'trim';
                if (is_null($default))$default = '';
                break;
            case 'Request':
                return $request;
            default:
                //TODO 暂时只支持基本数据类型
                break;
        }

        return $request->param($parameter->getName(), $filter, $default);
    }
}