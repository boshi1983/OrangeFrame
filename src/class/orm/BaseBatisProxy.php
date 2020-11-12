<?php

class BaseBatisProxy
{
    /**
     * @var Mysql
     * @inject Mysql
     */
    public $db;

    /**
     * 查询接口
     * @param string $sql
     * @return array|bool|string
     */
    public function query(string $sql) {
        return $this->db->query($sql);
    }

    /**执行接口，update/delete/insert
     * @param string $sql
     * @return bool|int|string
     */
    public function execute(string $sql) {
        return $this->db->execute($sql);
    }

    /**
     * @param string $field in字段
     * @param array $arr in数组
     * @param bool $useOrder 是否需要排序
     * @return string
     */
    protected function foreach($collection, $item, $content, $open, $separator, $close)
    {
        $rt = [];
        foreach($collection as $k => $v) {
            $rt[] = str_replace('#{'.$item.'}', $v, $content);
        }
        return $open . join($separator, $rt) . $close;
    }

    public function getLastInsID()
    {
        return $this->db->getLastInsID();
    }

    public function getLastSql():int
    {
        return intval($this->db->getLastSql());
    }

    public function begin()
    {
        $this->db->startTrans();
    }

    public function commit():bool
    {
        return $this->db->commit();
    }

    public function bindParam(string $name, $value)
    {
        $this->db->bindParam($name, $value);
    }

    public function getOne(string $sql)
    {
        return $this->db->getOne($sql);
    }

    public function get($sql)
    {
        return $this->db->get($sql);
    }
}