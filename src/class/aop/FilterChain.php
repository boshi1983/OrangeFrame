<?php


class FilterChain
{
    protected $filterHead;
    protected $filterTail;

    public function __construct()
    {
        $this->filterHead = new FilterHead();
        $this->filterTail = new FilterTail();

        $this->filterHead->next = $this->filterTail;
        $this->filterTail->prev = $this->filterHead;
    }


    /**
     * @param $filter
     * @return $this
     */
    public function add($filter)
    {
        $filter->next = $this->filterTail;
        $filter->prev = $this->filterTail->prev;

        $this->filterTail->prev = $filter;
        $filter->prev->next = $filter;

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

        if (!empty($this->filterHead)) {
            $rt = $this->filterHead->doFilter($param);
        }

        return $rt;
    }

}