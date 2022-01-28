<?php

class RootMapping extends BaseBean
{
    protected $get = [];
    protected $post = [];

    /**
     * @return array
     */
    public function getGet($path): ?MethodMapping
    {
        return $this->get[$path] ?? null;
    }

    /**
     * @param array $get
     */
    public function setGet(array $get): void
    {
        $this->get = $get;
    }

    /**
     * @return array
     */
    public function getPost($path): ?MethodMapping
    {
        return $this->post[$path] ?? null;
    }

    /**
     * @param array $post
     */
    public function setPost(array $post): void
    {
        $this->post = $post;
    }

    public function addGet($name, $mapping) {
        $this->get[$name] = $mapping;
    }

    public function addPost($name, $mapping) {
        $this->post[$name] = $mapping;
    }
}