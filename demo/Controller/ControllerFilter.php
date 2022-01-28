<?php


class ControllerFilter extends BaseFilter
{
    /**
     * @var MethodMapping
     */
    protected $methodMapping;

    /**
     * @var BaseServer
     */
    protected $server;

    /**
     * MainDoFilter constructor.
     * @param Request $request
     */
    public function __construct(BaseServer $server, Request $request)
    {
        $this->server = $server;
        $controllerLoader = $this->server->get('ControllerLoader');

        /**
         * @var MethodMapping $methodMapping
         */
        $this->methodMapping = $controllerLoader->get($request);
    }

    /**
     * @param mixed $data
     * @param FilterChain $link
     * @return array|mixed|void
     */
    function doFilter($data)
    {
        $controller = $this->server->get($this->methodMapping->getClassMapping()->getName());
        return $controller->distribute($this->methodMapping->getName(), $data);
    }
}