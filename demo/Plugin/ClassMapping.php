<?php

class ClassMapping extends BaseBean
{
    protected $path;
    protected $name;
    protected $getMapping;
    protected $postMapping;
    protected $requestMapping;

    public function fullGetMapping() {
        $mapping = $this->getMapping;
        if (empty($mapping)) {
            $mapping = $this->requestMapping;
        }

        return $mapping;
    }

    public function fullPostMapping() {
        $mapping = $this->postMapping;
        if (empty($mapping)) {
            $mapping = $this->requestMapping;
        }

        return $mapping;
    }

    public function setByJson($json) {
        $this->setPath($json['path'] ?? '');
        $this->setName($json['name'] ?? '');
        $this->setPostMapping($json['postMapping'] ?? '');
        $this->setGetMapping($json['getMapping'] ?? '');
        $this->setRequestMapping($json['requestMapping'] ?? '');
    }

    /**
     * @return mixed
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param mixed $path
     */
    public function setPath($path): void
    {
        $this->path = $path;
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