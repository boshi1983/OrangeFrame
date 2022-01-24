<?php


class CompareNode
{
    private $test;
    private $include;
    private $content;

    /**
     * @param $test
     * @param $include
     * @param $content
     */
    public function __construct($test, $include, $content)
    {
        $this->test = $test;
        $this->include = $include;
        $this->content = $content;
    }


    /**
     * @return mixed
     */
    public function getTest()
    {
        return $this->test;
    }

    /**
     * @param mixed $test
     */
    public function setTest($test): void
    {
        $this->test = $test;
    }

    /**
     * @return mixed
     */
    public function getInclude()
    {
        return $this->include;
    }

    /**
     * @param mixed $include
     */
    public function setInclude($include): void
    {
        $this->include = $include;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param mixed $content
     */
    public function setContent($content): void
    {
        $this->content = $content;
    }


}
