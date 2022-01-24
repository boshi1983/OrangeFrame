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
     * @param $runtime
     * @return mixed
     */
    function doFilter($runtime) {
        return $this->next->doFilter($runtime);
    }
}