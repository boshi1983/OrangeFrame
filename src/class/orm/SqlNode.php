<?php


class SqlNode implements BaseNode
{
    private $sql;

    /**
     * @param $sql
     */
    public function __construct($sql)
    {
        $this->sql = trim($sql);
    }


    /**
     * @param $idx
     * @return string
     */
    function getString($idx): string
    {
        return '$sql .= \' \' . $this->' . $this->sql . ';' . PHP_EOL;
    }

}