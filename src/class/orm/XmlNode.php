<?php


class XmlNode {
    /**
     * @var string
     */
    private $id = '';

    /**
     * @var string
     */
    private $tag = '';

    /**
     * @var string
     */
    private $resultType = '';

    /**
     * @var false
     */
    private $transaction = false;

    /**
     * @var string
     */
    private $func = '';

    /**
     * @var string
     */
    private $sql = '';

    /**
     * XmlNode constructor.
     * @param string $id
     * @param string $tag
     * @param string $resultType
     * @param false $transaction
     */
    public function __construct(string $id, string $tag, string $resultType = '', $transaction = false)
    {
        $this->id = $id;
        $this->tag = $tag;
        $this->resultType = $resultType;
        $this->transaction = $transaction;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * @param mixed $tag
     */
    public function setTag($tag): void
    {
        $this->tag = $tag;
    }

    /**
     * @return mixed
     */
    public function getResultType()
    {
        return $this->resultType;
    }

    /**
     * @param mixed $resultType
     */
    public function setResultType($resultType): void
    {
        $this->resultType = $resultType;
    }

    /**
     * @return mixed
     */
    public function getTransaction()
    {
        return $this->transaction;
    }

    /**
     * @param mixed $transaction
     */
    public function setTransaction($transaction): void
    {
        $this->transaction = $transaction;
    }

    /**
     * @return mixed
     */
    public function getFunc()
    {
        return $this->func;
    }

    /**
     * @param mixed $func
     */
    public function setFunc($func): void
    {
        $this->func = $func;
    }

    /**
     * @return mixed
     */
    public function getSql()
    {
        return $this->sql;
    }

    /**
     * @param mixed $sql
     */
    public function setSql($sql): void
    {
        $this->sql = $sql;
    }
}