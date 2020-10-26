<?php


class ControllerFilter implements BaseFilter
{
    /**
     * @var BaseController
     */
    protected $controller;

    /**
     * MainDoFilter constructor.
     * @param BaseController $controller
     */
    public function __construct(BaseController $controller)
    {
        $this->controller = $controller;
    }


    /**
     * @param mixed $data
     * @param FilterChain $link
     * @return array|mixed|void
     */
    function doFilter($data, FilterChain $link)
    {
        return $this->controller->Process();
    }
}