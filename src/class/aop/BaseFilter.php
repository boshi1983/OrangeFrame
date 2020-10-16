<?php


interface BaseFilter
{
    /**
     * @param FilterChain $link
     * @return mixed
     */
    function doFilter($data, $link);
}