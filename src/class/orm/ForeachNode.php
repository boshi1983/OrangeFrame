<?php


class ForeachNode implements BaseNode
{
    private $attribute;
    private $string;

    /**
     * @param $attribute
     */
    public function __construct($attribute, $string)
    {
        $this->attribute = $attribute;
        $this->string = $string;
    }

    private function getAttribute($key)
    {
        if (empty($key)) {
            return $this->attribute;
        }

        return $this->attribute[strtoupper($key)] ?? '';
    }

    /**
     * @return string
     */
    function getString($idx): string
    {
        $rtString = '';

        $rtString .= '$sql .= $this->foreach(';

        $rtString .= $this->getAttribute('collection');
        $rtString .= ',\'';
        $rtString .= $this->getAttribute('item');
        $rtString .= '\',\'';
        $rtString .= $this->string;
        $rtString .= '\',\'';
        $rtString .= $this->getAttribute('open');
        $rtString .= '\',\'';
        $rtString .= $this->getAttribute('separator');
        $rtString .= '\',\'';
        $rtString .= $this->getAttribute('close');
        $rtString .= '\'';

        $rtString .= ');';
        $rtString .= PHP_EOL;

        return $rtString;
    }

}