<?php


/**
 * Class XmlNode
 * @package Orange
 */
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
     * @var array
     */
    private $children = [];

    /**
     * @var array
     */
    private $attributes = [];

    /**
     * @var string
     */
    private $sql = '';

    /**
     * @var array
     */
    private $layer = [];

    /**
     * @var array
     */
    private $excludeParam = [];

    /**
     * XmlNode constructor.
     * @param string $id
     * @param string $tag
     * @param string $resultType
     * @param false $transaction
     */
    public function __construct(array $attributes, string $tag)
    {
        $this->id = $attributes['ID'] ?? '';
        $this->tag = strtolower($tag);
        $this->resultType = $attributes['RESULTTYPE'] ?? '';
        $this->transaction = $attributes['TRANSACTION'] ?? '';

        $this->setAttribute($attributes);
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

    public function addChind($node): void
    {
        $this->children[] = $node;
    }

    /**
     * @param $index
     * @return array|string
     */
    public function getChildren($index = null)
    {
        if (is_null($index)) {
            return $this->children;
        }
        return $this->children[$index];
    }

    public function setAttribute($attributes):void
    {
        $this->attributes = $attributes;
    }

    public function getAttribute($key)
    {
        if (empty($key)) {
            return $this->attributes;
        }

        return $this->attributes[strtoupper($key)] ?? '';
    }

    /**
     * @return array
     */
    public function getLayer(): array
    {
        return $this->layer;
    }

    /**
     * @param array $layer
     */
    public function setLayer(array $layer): void
    {
        $this->layer = $layer;
    }

    /**
     * @param BaseNode $node
     * @return void
     */
    public function addlayer(BaseNode $node):void
    {
        $this->layer[] = $node;
    }

    /**
     * @return mixed|string
     */
    public function getNameSpace()
    {
        return $this->attributes['NAMESPACE'] ?? '';
    }

    public function addExcludeParam($paramName) {
        if (empty($paramName)) {
            return;
        }
        $this->excludeParam[] = str_replace('$', '', $paramName);
    }

    /**
     * @param array $paramList
     * @return array
     */
    public function excludeParam(array $paramList)
    {
        if (empty($this->excludeParam)) {
            return $paramList;
        }
        $rt = [];
        foreach ($paramList as $param) {
            if (!in_array($param['name'], $this->excludeParam)) {
                $rt[] = $param;
            }
        }
        return $rt;
    }
}