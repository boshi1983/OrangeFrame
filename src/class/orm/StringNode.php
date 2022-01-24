<?php


class StringNode implements BaseNode
{
    private $string;

    /**
     * @param $string
     */
    public function __construct($string)
    {
        $this->string = trim($string);
    }

    /**
     * @return string
     */
    function getString($idx): string
    {
        if ($idx == 0) {
            return '$sql = \'' . $this->string . ' \';' . PHP_EOL;
        }

        return '$sql .= \' ' . $this->string . ' \';' . PHP_EOL;
    }

}