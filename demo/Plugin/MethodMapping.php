<?php

class MethodMapping extends BaseBean
{
    /**
     * @var ClassMapping
     */
    protected $classMapping;
    protected $name;
    protected $getMapping;
    protected $postMapping;
    protected $requestMapping;

    public function fullGetMapping() {
        $mapping = $this->getMapping;
        if (empty($mapping)) {
            $mapping = $this->requestMapping;
        }

        if (empty($mapping)) {
            return '';
        }

        return $this->classMapping->fullGetMapping() . $mapping;
    }

    public function fullPostMapping() {
        $mapping = $this->postMapping;
        if (empty($mapping)) {
            $mapping = $this->requestMapping;
        }

        if (empty($mapping)) {
            return '';
        }
        return $this->classMapping->fullPostMapping() . $mapping;
    }

    public function setByJson($json) {
        $classMapping = new ClassMapping();
        $classMapping->setByJson($json['classMapping']);

        $this->setName($json['name'] ?? '');
        $this->setPostMapping($json['postMapping'] ?? '');
        $this->setGetMapping($json['getMapping'] ?? '');
        $this->setRequestMapping($json['requestMapping'] ?? '');

        $this->setClassMapping($classMapping);
    }

    /**
     * @return mixed
     */
    public function getClassMapping()
    {
        return $this->classMapping;
    }

    /**
     * @param mixed $classMapping
     */
    public function setClassMapping($classMapping): void
    {
        $this->classMapping = $classMapping;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getGetMapping()
    {
        return $this->getMapping;
    }

    /**
     * @param mixed $getMapping
     */
    public function setGetMapping($getMapping): void
    {
        $this->getMapping = $getMapping;
    }

    /**
     * @return mixed
     */
    public function getPostMapping()
    {
        return $this->postMapping;
    }

    /**
     * @param mixed $postMapping
     */
    public function setPostMapping($postMapping): void
    {
        $this->postMapping = $postMapping;
    }

    /**
     * @return mixed
     */
    public function getRequestMapping()
    {
        return $this->requestMapping;
    }

    /**
     * @param mixed $requestMapping
     */
    public function setRequestMapping($requestMapping): void
    {
        $this->requestMapping = $requestMapping;
    }
}