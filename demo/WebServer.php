<?php


class WebServer extends BaseServer
{
    /**
     * @throws OrangeBatisException|ReflectionException
     */
    public function run()
    {
        //创建责任链
        $link = new FilterChain();

        $request = new Request();
        //增加json过滤器
        if ($request->isAjax() || $request->isPost()) {
            $link->add(new JsonFilter());
        }

        //创建控制器过滤器
        $controllerFilter = new ControllerFilter($this, $request);

        //对$controllerFilter增加动态代理
        $proxy = Proxy::newProxyInstance($controllerFilter, new class implements InvocationHandler
        {
            public function invoke($proxy, $target, $method, $args)
            {
                $starttime = microtime(true);
                $rt = $method->invokeArgs($target, $args);
                $endtime = microtime(true);

                $logcontent = '本次执行时间为：' . ($endtime - $starttime) . 's';
                error_log($logcontent . PHP_EOL . PHP_EOL, 3, RES_PATH . 'log/' . date('Y-m-d').'.log');
                return $rt;
            }
        });

        //设置主代理
        $link->add($proxy);

        //执行责任链
        echo $link->doFilter($request);
    }
}