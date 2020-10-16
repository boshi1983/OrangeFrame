<?php

interface InvocationHandler
{
    /**
     * @param $proxy object 代理类实例
     * @param $target object 被代理类实例
     * @param $method \ReflectionMethod 代理方法反射
     * @param $args array 方法参数
     * @return mixed 返回值
     */
    public function invoke($proxy, $target, $method, $args);
}
