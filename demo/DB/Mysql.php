<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

/**
 * mysql数据库驱动
 */
class Mysql extends Driver
{
    /**
     * 取得数据表的字段信息
     * @access public
     * @param $tableName
     * @return array
     */
    public function getFields($tableName)
    {
        $this->initConnect(true);
        list($tableName) = explode(' ', $tableName);
        if (strpos($tableName, '.')) {
            list($dbName, $tableName) = explode('.', $tableName);
            $sql = 'SHOW COLUMNS FROM `' . $dbName . '`.`' . $tableName . '`';
        } else {
            $sql = 'SHOW COLUMNS FROM `' . $tableName . '`';
        }

        $result = $this->query($sql);
        $info = [];
        if ($result) {
            foreach ($result as $key => $val) {
                if (PDO::CASE_LOWER != $this->_linkID->getAttribute(PDO::ATTR_CASE)) {
                    $val = array_change_key_case($val, CASE_LOWER);
                }
                $info[$val['field']] = [
                    'name' => $val['field'],
                    'type' => $val['type'],
                    'notnull' => (bool)($val['null'] === ''), // not null is empty, null is yes
                    'default' => $val['default'],
                    'primary' => (strtolower($val['key']) == 'pri'),
                    'autoinc' => (strtolower($val['extra']) == 'auto_increment'),
                ];
            }
        }
        return $info;
    }

    /**
     * 取得数据库的表信息
     * @access public
     * @param string $dbName
     * @return array
     */
    public function getTables($dbName = '')
    {
        $sql = !empty($dbName) ? 'SHOW TABLES FROM ' . $dbName : 'SHOW TABLES ';
        $result = $this->query($sql);
        $info = [];
        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }
        return $info;
    }

    /**
     * 批量插入记录
     * @access public
     * @param array $dataSet 数据集
     * @param array $options 参数表达式
     * @param boolean $replace 是否replace
     * @return false | integer
     */
    public function insertAll(array $dataSet, $options = [], $replace = false)
    {
        $values = [];
        $this->model = $options['model'];
        if (!is_array($dataSet[0])) return false;
        $this->parseBind(!empty($options['bind']) ? $options['bind'] : []);
        $fields = array_map([$this, 'parseKey'], array_keys($dataSet[0]));
        foreach ($dataSet as $data) {
            $value = [];
            foreach ($data as $key => $val) {
                if (is_array($val) && 'exp' == $val[0]) {
                    $value[] = $val[1];
                } elseif (is_scalar($val)) {
                    if (0 === strpos($val, ':') && in_array($val, array_keys($this->bind))) {
                        $value[] = $this->parseValue($val);
                    } else {
                        $name = count($this->bind);
                        $value[] = ':' . $name;
                        $this->bindParam($name, $val);
                    }
                }
            }
            $values[] = '(' . implode(',', $value) . ')';
        }
        // 兼容数字传入方式
        $replace = (is_numeric($replace) && $replace > 0) ? true : $replace;
        $sql = (true === $replace ? 'REPLACE' : 'INSERT') . ' INTO ' . $this->parseTable($options['table']) . ' (' . implode(',', $fields) . ') VALUES ' . implode(',', $values) . $this->parseDuplicate($replace);
        $sql .= $this->parseComment(!empty($options['comment']) ? $options['comment'] : '');
        return $this->execute($sql, !empty($options['fetch_sql']));
    }

    /**
     * ON DUPLICATE KEY UPDATE 分析
     * @access protected
     * @param string|object|array $duplicate
     * @return string
     */
    public function parseDuplicate($duplicate)
    {
        // 布尔值或空则返回空字符串
        if (is_bool($duplicate) || empty($duplicate)) return '';

        if (is_string($duplicate)) {
            // field1,field2 转数组
            $duplicate = explode(',', $duplicate);
        } elseif (is_object($duplicate)) {
            // 对象转数组
            $duplicate = get_class_vars($duplicate);
        }
        $updates = [];
        foreach ((array)$duplicate as $key => $val) {
            if (is_numeric($key)) { // array('field1', 'field2', 'field3') 解析为 ON DUPLICATE KEY UPDATE field1=VALUES(field1), field2=VALUES(field2), field3=VALUES(field3)
                $updates[] = $this->parseKey($val) . "=VALUES(" . $this->parseKey($val) . ")";
            } else {
                if (is_scalar($val)) // 兼容标量传值方式
                    $val = ['value', $val];
                if (!isset($val[1])) continue;
                switch ($val[0]) {
                    case 'exp': // 表达式
                        $updates[] = $this->parseKey($key) . "=($val[1])";
                        break;
                    case 'value': // 值
                    default:
                        $name = count($this->bind);
                        $updates[] = $this->parseKey($key) . "=:" . $name;
                        $this->bindParam($name, $val[1]);
                        break;
                }
            }
        }
        if (empty($updates)) return '';
        return " ON DUPLICATE KEY UPDATE " . join(', ', $updates);
    }/** @noinspection PhpMissingParentCallCommonInspection */

    /**
     * 字段和表名处理
     * @access protected
     * @param string $key
     * @return string
     */
    protected function parseKey(string $key)
    {
        $key = trim($key);
        if (!is_numeric($key) && !preg_match('/[,\'\"\*\(\)`.\s]/', $key)) {
            $key = '`' . $key . '`';
        }
        return $key;
    }/** @noinspection PhpMissingParentCallCommonInspection */

    /**
     * 获取指定编号的记录.
     * @param $table
     * @param int $id 要获取的记录的编号.
     * @param string $field 字段名, 默认为'id'.
     * @return array
     */
    function load($table, int $id, $field = 'id')
    {
        if (is_numeric($id) && !empty($id)) {
            $sql = "SELECT * FROM {$table} WHERE {$field}={$id}";
        } else {
            $sql = "SELECT * FROM {$table} WHERE {$field}='{$id}'";
        }
        return $this->get($sql);
    }

    /**
     * 执行 SQL 语句, 返回结果的第一条记录(是一个对象).
     * @param string $sql
     * @return array
     */
    public function get($sql)
    {
        $rt = null;
        $result = $this->query($sql);
        if (!empty($result) && is_array($result)) {
            $rt = $result[0];
        }
        return $rt;
    }

    /**
     * @param $sql
     * @return bool|string
     */
    function getOne($sql)
    {
        if (stripos($sql, ' LIMIT ') === false) {
            $sql = trim($sql . ' LIMIT 1');
        }
        return $this->query($sql, false, true);
    }

    /**
     * 保存一条记录, 调用后, id被设置.
     * @param string $table
     * @param object|array $row
     * @return void
     */
    function save(string $table, $row)
    {
        $sqlA = [];
        foreach ($row as $k => $v) {
            if (!empty($k)) {
                $sqlA[] = $k . ' = \'' . $v . '\'';
            }
        }

        if (empty($sqlA))
            return;

        $sql = 'INSERT INTO ' . $table . ' SET ' . join(',', $sqlA);
        if ($this->execute($sql) != false) {
            if (is_object($row)) {
                $row->id = $this->lastInsID;
            } else if (is_array($row)) {
                $row['id'] = $this->lastInsID;
            }
        }
    }

    /**
     * 更新$arr[id]所指定的记录.
     * @param string $table
     * @param array|object $row 要更新的记录, 键名为id的数组项的值指示了所要更新的记录.
     * @param string $field 字段名, 默认为'id'.
     * @return int 影响的行数.
     */
    function update(string $table, $row, $field = 'id')
    {
        $id = 0;
        if (is_object($row)) {
            $id = $row->{$field};
            unset($row->{$field});
        } else if (is_array($row)) {
            $id = $row[$field];
            unset($row[$field]);
        }

        $sqlArr = [];
        foreach ($row as $k => $v) {
            $sqlArr[] = '`' . $k . '`' . ' = \'' . $v . '\'';
        }

        $sqlA = join(', ', $sqlArr);

        if (empty(trim($sqlA)) || empty($id))
            return 0;

        $sql = 'UPDATE ' . $table . ' set ' . $sqlA . ' where ' . $field . '=\'' . $id . '\'';
        return $this->execute($sql);
    }

    /**
     * 解析pdo连接的dsn信息
     * @access public
     * @param array $config 连接信息
     * @return string
     */
    public function parseDsn(array $config)
    {
        $dsn = 'mysql:dbname=' . $config['database'] . ';host=' . $config['hostname'];
        if (!empty($config['hostport'])) {
            $dsn .= ';port=' . $config['hostport'];
        } elseif (!empty($config['socket'])) {
            $dsn .= ';unix_socket=' . $config['socket'];
        }

        if (!empty($config['charset'])) {
            //为兼容各版本PHP,用两种方式设置编码
            $this->options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES ' . $config['charset'];
            $dsn .= ';charset=' . $config['charset'];
        }
        return $dsn;
    }
}
