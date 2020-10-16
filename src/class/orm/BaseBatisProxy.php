<?php

class BaseBatisProxy
{
    /**
     * @var Mysql
     * @inject Mysql
     */
    public $db;

    public function &query(string $sql) {
        return $this->db->query($sql);
    }
}