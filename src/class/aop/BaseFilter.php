<?php


class BaseFilter implements iFilter
{
    /**
     * @var BaseFilter
     */
    public $prev;

    /**
     * @var BaseFilter
     */
    public $next;

    /**
     * @param Request $request
     * @return mixed
     */
    function doFilter(Request $request) {
        return $this->next->doFilter($request);
    }
}