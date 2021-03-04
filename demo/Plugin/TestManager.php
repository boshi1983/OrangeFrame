<?php


class TestManager
{
    /**
     * @var string
     */
    private $string;

    private $integer;

    private $const;

    /**
     * TestManager constructor.
     */
    public function __construct($strname, $intname, $constname)
    {
        $this->string = $strname;
        $this->integer = $intname;
        $this->const = $constname;
    }

    public function show()
    {
        return "string：{$this->string}; integer：{$this->integer}; const：{$this->const}";
    }
}