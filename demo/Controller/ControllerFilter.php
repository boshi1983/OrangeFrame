<?php


class ControllerFilter extends BaseFilter
{
    /**
     * @var MethodMapping
     */
    protected $methodMapping;

    /**
     * MainDoFilter constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->methodMapping = $request->genObject('MappingProcessor')->get($request);
    }

    /**
     * @param Request $request
     * @param FilterChain $link
     * @return array|mixed|void
     */
    function doFilter($request)
    {
        $controller = $request->genObject($this->methodMapping->getClassMapping()->getName(), $this->methodMapping->getClassMapping()->getPath());
        $distribute = $controller->distribute($this->methodMapping->getName(), $request);
        return $distribute;
    }
}