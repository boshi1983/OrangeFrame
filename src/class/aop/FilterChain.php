<?php


class FilterChain
{
    protected $filterList;
    protected $curFilter = -1;

    /**
     * @param $filter
     * @return $this
     */
    public function add($filter)
    {
        $this->filterList[] = $filter;
        return $this;
    }

    /**
     * @param $param
     * @param FilterChain $link
     * @return mixed
     */
    function doFilter($param)
    {
        $rt = $param;
        $this->curFilter++;
        if ($this->curFilter < count($this->filterList)) {
            $filter = $this->filterList[$this->curFilter];
            $rt = $filter->doFilter($param, $this);
        }
        return $rt;
    }

}