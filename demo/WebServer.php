<?php


class WebServer extends BaseServer
{
    public function run()
    {
        $controller = &$this->_container->get('TastController');
        $filter = new ControllerFilter($controller);

        $proxy = Proxy::newProxyInstance($filter, new class implements InvocationHandler
        {
            public function invoke($proxy, $target, $method, $args)
            {
                $logcontent = '';

                $starttime = microtime(true);

                $rt = $method->invokeArgs($target, $args);

                $endtime = microtime(true);

                $logcontent .= '本次执行时间为：' . ($endtime - $starttime) . 's';

                error_log($logcontent . PHP_EOL . PHP_EOL, 3, RES_PATH . 'log/' . date('Y-m-d').'.log');
                return $rt;
            }
        });

        $link = new FilterChain();
        //$link->add(new JsonFilter());
        $link->add($proxy);
        $link->doFilter([]);
    }
}