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

    public function getLastSql():string
    {
        return $this->db->getLastSql();
    }

    public function begin()
    {
        $this->db->startTrans();
    }

    public function commit():bool
    {
        return $this->db->commit();
    }

    public function bindParam(string $name, $value, $show = false)
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

    public function getRowCount()
    {
        return $this->db->getRowCount();
    }

    private function filterKey($k)
    {
        return str_replace('-', '_', $k);
    }

    /**
     * @param $param
     * @return array
     */
    public function getInsertInfo($data)
    {
        reset($data);
        $fields = [];
        $values = [];
        $datas = [];
        $length = 1;

        if (key($data) === 0 && is_array($data[0])) {
            $length = count($data);
            foreach ($data as $_k => $_v) {
                foreach ($_v as $_f => $_fv) {
                    if (!is_null($_fv)) {
                        $_f = trim($_f, '`');
                        $fieldKey = $this->filterKey($_f) . '_' . $_k;
                        //:$k不能以数字开头，所以添加下划线前缀
                        if (!in_array("`{$_f}`", $fields)) {
                            $fields[] = "`{$_f}`";
                        }
                        $values[$_k][] = ":{$fieldKey}";
                        $datas[$fieldKey] = $_fv;
                    }
                }
                $values[$_k] = '(' . implode(',', $values[$_k]) . ')';
            }
        } else {
            foreach ($data as $_k => $_v) {
                if (!is_null($_v)) {
                    $_k = trim($_k, '`');
                    $fieldKey = $this->filterKey($_k);
                    //:$k不能以数字开头，所以添加下划线前缀
                    $fields[] = "`{$_k}`";
                    $values[] = ":{$fieldKey}";
                    $datas[$fieldKey] = $_v;
                }
            }
            $values = '(' . implode(',', $values) . ')';
        }
        $fields = implode(',', $fields);
        is_array($values) && $values = implode(',', $values);

        return [$fields, $values, $datas, $length];
    }
}