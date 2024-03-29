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
 * Class Driver
 */
abstract class Driver
{
    // 当前操作所属的模型名
    /**
     * @var \PDOStatement
     */
    protected $PDOStatement = null;
    // 当前SQL指令
    protected $model = '_orange_';
    protected $queryStr = '';
    // 最后插入ID
    protected $modelSql = [];
    // 返回或者影响记录数
    protected $lastInsID = null;
    // 事务指令数
    protected $numRows = 0;
    // 错误信息
    protected $transTimes = 0;
    // 数据库连接ID 支持多个连接
    protected $error = '';
    // 当前连接ID
    protected $linkID = [];
    // 数据库连接参数配置
    /**
     * @var PDO
     */
    protected $_linkID = null;
    // 数据库表达式
    protected $config = [
        'type' => '',// 数据库类型
        'hostname' => '',    // 服务器地址 ,
        'database' => '',             // 数据库名 ,
        'username' => '',             // 用户名 ,
        'password' => '',             // 密码 ,
        'hostport' => '',             // 端口 ,
        'dsn' => '',             // ,
        'charset' => '',         // 数据库编码默认采用utf8 ,
        'params' => [],    // 数据库连接参数
        'prefix' => '',         // 数据库表前缀
        'debug' => false,      // 数据库调试模式
        'deploy' => 0,          // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
        'rw_separate' => false,      // 数据库读写是否分离 主从式有效
        'master_num' => 1,          // 读写分离后 主服务器数量
        'slave_no' => '',         // 指定从服务器序号
        'db_like_fields' => '',
    ];
    // 查询表达式
    protected $exp = ['eq' => '=', 'neq' => '<>', 'gt' => '>', 'egt' => '>=', 'lt' => '<', 'elt' => '<=', 'notlike' => 'NOT LIKE', 'like' => 'LIKE', 'in' => 'IN', 'notin' => 'NOT IN', 'not in' => 'NOT IN', 'between' => 'BETWEEN', 'not between' => 'NOT BETWEEN', 'notbetween' => 'NOT BETWEEN'];
    // 查询次数
    protected $selectSql = 'SELECT%DISTINCT% %FIELD% FROM %TABLE%%FORCE%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER%%LIMIT% %UNION%%LOCK%%COMMENT%';
    // 执行次数
    protected $queryTimes = 0;
    // PDO连接参数
    protected $executeTimes = 0;
    protected $options = [
        PDO::ATTR_CASE => PDO::CASE_LOWER,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_PERSISTENT => true
    ]; // 参数绑定
    protected $bind = [];
    protected $timeLogger = [];

    //------------------------------------------------------------------------------------

    public function setConfig($config = '') {
        if (!empty($config)) {
            $this->config = array_merge($this->config, $config);
            if (is_array($this->config['params'])) {
                $this->options = $this->config['params'] + $this->options;
            }
        }
    }

    /**
     * @param $field
     * @param $arr
     * @param bool $useOrder
     * @return string
     */
    public function create_in($field, $arr, $useOrder = true)
    {
        if (empty($arr))
            return ' ' . $field . ' = 0 ';

        $order = '';
        if ($useOrder) {
            $order = ' ORDER BY FIELD(' . $field . ', ' . implode(',', $arr) . ')';
        }

        return ' ' . $field . ' in (' . implode(',', $arr) . ') ' . $order;
    }

    /**
     * 启动事务
     * @access public
     * @return boolean
     */
    public function startTrans()
    {
        $this->initConnect(true);
        if (!$this->_linkID) return false;
        //数据rollback 支持
        if ($this->transTimes == 0) {
            $this->_linkID->beginTransaction();
            $this->error = '';
        }
        ++$this->transTimes;
        return true;
    }

    /**
     * 初始化数据库连接
     * @access protected
     * @param boolean $master 主服务器
     */
    protected function initConnect($master = true)
    {
        if (!empty($this->config['deploy']))
            // 采用分布式数据库
            $this->_linkID = $this->multiConnect($master);
        else
            // 默认单数据库
            if (!$this->_linkID) $this->_linkID = $this->connect();
    }

    /**
     * 连接分布式服务器
     * @access protected
     * @param boolean $master 主服务器
     * @return object
     */
    protected function multiConnect($master = false)
    {
        // 分布式数据库配置解析
        $_config['username'] = explode(',', $this->config['username']);
        $_config['password'] = explode(',', $this->config['password']);
        $_config['hostname'] = explode(',', $this->config['hostname']);
        $_config['hostport'] = explode(',', $this->config['hostport']);
        $_config['database'] = explode(',', $this->config['database']);
        $_config['dsn'] = explode(',', $this->config['dsn']);
        $_config['charset'] = explode(',', $this->config['charset']);

        $m = floor(mt_rand(0, $this->config['master_num'] - 1));
        // 数据库读写是否分离
        if ($this->config['rw_separate']) {
            // 主从式采用读写分离
            if ($master)
                // 主服务器写入
                $r = $m;
            else {
                if (is_numeric($this->config['slave_no'])) {// 指定服务器读
                    $r = $this->config['slave_no'];
                } else {
                    // 读操作连接从服务器
                    $r = floor(mt_rand($this->config['master_num'], count($_config['hostname']) - 1));   // 每次随机连接的数据库
                }
            }
        } else {
            // 读写操作不区分服务器
            $r = floor(mt_rand(0, count($_config['hostname']) - 1));   // 每次随机连接的数据库
        }

        $db_master = [];
        if ($m != $r) {
            $db_master['username'] = isset($_config['username'][$m]) ? $_config['username'][$m] : $_config['username'][0];
            $db_master['password'] = isset($_config['password'][$m]) ? $_config['password'][$m] : $_config['password'][0];
            $db_master['hostname'] = isset($_config['hostname'][$m]) ? $_config['hostname'][$m] : $_config['hostname'][0];
            $db_master['hostport'] = isset($_config['hostport'][$m]) ? $_config['hostport'][$m] : $_config['hostport'][0];
            $db_master['database'] = isset($_config['database'][$m]) ? $_config['database'][$m] : $_config['database'][0];
            $db_master['dsn'] = isset($_config['dsn'][$m]) ? $_config['dsn'][$m] : $_config['dsn'][0];
            $db_master['charset'] = isset($_config['charset'][$m]) ? $_config['charset'][$m] : $_config['charset'][0];
        }
        $db_config = [
            'username' => isset($_config['username'][$r]) ? $_config['username'][$r] : $_config['username'][0],
            'password' => isset($_config['password'][$r]) ? $_config['password'][$r] : $_config['password'][0],
            'hostname' => isset($_config['hostname'][$r]) ? $_config['hostname'][$r] : $_config['hostname'][0],
            'hostport' => isset($_config['hostport'][$r]) ? $_config['hostport'][$r] : $_config['hostport'][0],
            'database' => isset($_config['database'][$r]) ? $_config['database'][$r] : $_config['database'][0],
            'dsn' => isset($_config['dsn'][$r]) ? $_config['dsn'][$r] : $_config['dsn'][0],
            'charset' => isset($_config['charset'][$r]) ? $_config['charset'][$r] : $_config['charset'][0],
        ];
        return $this->connect($db_config, $r, $r == $m ? false : $db_master);
    }

    /**
     * 连接数据库方法
     * @access public
     * @param array $config
     * @param int $linkNum
     * @param bool $autoConnection
     * @return PDO
     */
    public function connect($config = [], $linkNum = 0, $autoConnection = false)
    {
        if (!isset($this->linkID[$linkNum])) {
            if (empty($config)) {
                $config = $this->config;
            }
            try {
                if (empty($config['dsn'])) {
                    $config['dsn'] = $this->parseDsn($config);
                }
                if (version_compare(PHP_VERSION, '5.3.6', '<=')) {
                    // 禁用模拟预处理语句
                    $this->options[PDO::ATTR_EMULATE_PREPARES] = false;
                }

                $this->linkID[$linkNum] = new PDO($config['dsn'], $config['username'], $config['password'], $this->options);
            } catch (PDOException $e) {

                echo $e->getMessage();
                if ($autoConnection) {
                    return $this->connect($autoConnection, $linkNum);
                }
            }
        }
        return $this->linkID[$linkNum];
    }

    /**
     * 解析pdo连接的dsn信息
     * @access public
     * @param array $config 连接信息
     * @return string
     */
    abstract function parseDsn(array $config);

    /**
     * 用于非自动提交状态下面的查询提交
     * @access public
     * @return boolean
     */
    public function commit()
    {
        $rt = true;
        if ($this->transTimes > 0) {
            if (empty($this->error)) {
                $this->_linkID->commit();
            } else {
                $this->_linkID->rollBack();
                $rt = false;
            }
            $this->transTimes = 0;
            $this->error = '';
        }
        return $rt;
    }

    /**
     * 数据库错误信息
     * 并显示当前的SQL语句
     * @access public
     */
    public function error()
    {
        if ($this->PDOStatement) {
            $error = $this->PDOStatement->errorInfo();
            $this->error = "$error[1] ({$error[0]}): $error[2] ";
        } else {
            $this->error = '';
        }
        if ('' != $this->queryStr) {
            $this->error .= "\n [ SQL语句 ] : " . $this->queryStr;
        }
        // 记录错误日志
        echo $this->error;
    }

    /**
     * 获得查询次数
     * @access public
     * @param boolean $execute 是否包含所有查询
     * @return integer
     */
    public function getQueryTimes($execute = false)
    {
        return $execute ? $this->queryTimes + $this->executeTimes : $this->queryTimes;
    }

    /**
     * 获得执行次数
     * @access public
     * @return integer
     */
    public function getExecuteTimes()
    {
        return $this->executeTimes;
    }

    /**
     * 插入记录
     * @access public
     * @param mixed $data 数据
     * @param array $options 参数表达式
     * @param boolean $replace 是否replace
     * @return false | integer
     */
    public function insert($data, $options = [], $replace = false)
    {
        $values = $fields = [];
        $this->model = $options['model'];
        $this->parseBind(!empty($options['bind']) ? $options['bind'] : []);
        foreach ($data as $key => $val) {
            if (is_array($val) && 'exp' == $val[0]) {
                $fields[] = $this->parseKey($key);
                $values[] = $val[1];
            } elseif (is_null($val)) {
                $fields[] = $this->parseKey($key);
                $values[] = 'NULL';
            } elseif (is_scalar($val)) { // 过滤非标量数据
                $fields[] = $this->parseKey($key);
                if (0 === strpos($val, ':') && in_array($val, array_keys($this->bind))) {
                    $values[] = $this->parseValue($val);
                } else {
                    $name = count($this->bind);
                    $values[] = ':' . $name;
                    $this->bindParam($name, $val);
                }
            }
        }
        // 兼容数字传入方式
        $replace = (is_numeric($replace) && $replace > 0) ? true : $replace;
        $sql = (true === $replace ? 'REPLACE' : 'INSERT') . ' INTO ' . $this->parseTable($options['table']) . ' (' . implode(',', $fields) . ') VALUES (' . implode(',', $values) . ')' . $this->parseDuplicate($replace);
        $sql .= $this->parseComment(!empty($options['comment']) ? $options['comment'] : '');
        return $this->execute($sql, !empty($options['fetch_sql']));
    }

    /**
     * 参数绑定分析
     * @access protected
     * @param array $bind
     */
    public function parseBind(array $bind)
    {
        $this->bind = array_merge($this->bind, $bind);
    }

    /**
     * 参数绑定
     * @access protected
     * @param string $name 绑定参数名
     * @param mixed $value 绑定值
     * @return void
     */
    public function bindParam(string $name, $value)
    {
        if (is_bool($value)) {
            $this->bind[$name] = [$value, PDO::PARAM_BOOL];
        } else if (is_float($value) || is_numeric($value)) {
            $this->bind[$name] = [$value, PDO::PARAM_INT];
        } else {
            $this->bind[$name] = $value;
        }
    }

    /**
     * ON DUPLICATE KEY UPDATE 分析
     * @access protected
     * @param mixed $duplicate
     * @return string
     */
    abstract function parseDuplicate($duplicate);

    /**
     * 执行语句
     * @access public
     * @param string $str sql指令
     * @param boolean $fetchSql 不执行只是获取SQL
     * @return string|int|bool
     */
    public function execute(string $str, $fetchSql = false)
    {
        $this->initConnect(true);
        if (!$this->_linkID) {
            return false;
        }
        $this->queryStr = $str;
        if ($fetchSql) {
            return $this->queryStr;
        }
        //释放前次的查询结果
        if (!empty($this->PDOStatement)) $this->free();
        ++$this->executeTimes;

        // 记录开始执行时间
        $this->debug(true);
        $this->PDOStatement = $this->_linkID->prepare($str);
        if (false === $this->PDOStatement) {
            $this->error();
            return false;
        }
        foreach ($this->bind as $key => $val) {
            if (is_array($val)) {
                $this->PDOStatement->bindValue($key, $val[0], $val[1]);
            } else {
                $this->PDOStatement->bindValue($key, $val);
            }
        }
        $this->bind = [];
        $result = false;
        try {
            $result = $this->PDOStatement->execute();
        } catch (PDOException $e) {
        }
        $this->debug(false);

        if (false === $result) {
            $this->error();
            return false;
        } else {
            $this->numRows = $this->PDOStatement->rowCount();
            if (preg_match("/^\s*(INSERT\s+INTO|REPLACE\s+INTO)\s+/i", $str)) {
                $this->lastInsID = $this->_linkID->lastInsertId();
            }
            return $this->numRows;
        }
    }

    /**
     * 释放查询结果
     * @access public
     */
    public function free()
    {
        $this->PDOStatement = null;
    }

    /**
     * 数据库调试 记录当前SQL
     * @access protected
     * @param boolean $start 调试开始标记 true 开始 false 结束
     */
    protected function debug(bool $start)
    {
        if ($this->config['debug']) {// 开启数据库调试模式
            if ($start) {
                $this->saveTime('queryStartTime');
            } else {
                $this->modelSql[$this->model] = $this->queryStr;
                //$this->model  =   '_think_';
                // 记录操作结束时间
                $this->saveTime('queryEndTime');
            }
        }
    }

    /**
     * @param $param1
     * @param null $param2
     * @return int|number
     */
    protected function saveTime($param1, $param2 = null)
    {
        if ($param2 == null) {
            $this->timeLogger[$param1] = time();
        } else {
            return abs($this->timeLogger[$param1] - $this->timeLogger[$param2]);
        }
        return 0;
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
                } elseif (is_null($val)) {
                    $value[] = 'NULL';
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
            $values[] = 'SELECT ' . implode(',', $value);
        }

        $strFiles = '( ' . implode(',', $fields) . ' )';
        $sql = 'INSERT INTO ' . $this->parseTable($options['table']) . $strFiles . implode(' UNION ALL ', $values);
        $sql .= $this->parseComment(!empty($options['comment']) ? $options['comment'] : '');
        return $this->execute($sql, !empty($options['fetch_sql']));
    }

    /**
     * 通过Select方式插入记录
     * @access public
     * @param string $fields 要插入的数据表字段名
     * @param string $table 要插入的数据表名
     * @param array $options
     * @return false|int
     * @internal param array $option 查询数据参数
     */
    public function selectInsert(string $fields, string $table, $options = [])
    {
        $this->model = $options['model'];
        $this->parseBind(!empty($options['bind']) ? $options['bind'] : []);
        if (is_string($fields)) $fields = explode(',', $fields);
        array_walk($fields, [$this, 'parseKey']);
        $strFields = ' (' . implode(',', $fields) . ') ';
        $sql = 'INSERT INTO ' . $this->parseTable($table) . $strFields;
        $sql .= $this->buildSelectSql($options);
        return $this->execute($sql, !empty($options['fetch_sql']));
    }

    /**
     * 更新记录
     * @access public
     * @param mixed $data 数据
     * @param array $options 表达式
     * @return false | integer
     */
    public function update2($data, array $options)
    {
        $this->model = $options['model'];
        $this->parseBind(!empty($options['bind']) ? $options['bind'] : []);
        $table = $this->parseTable($options['table']);
        $sql = 'UPDATE ' . $table . $this->parseSet($data);
        if (strpos($table, ',')) {// 多表更新支持JOIN操作
            $sql .= $this->parseJoin(!empty($options['join']) ? $options['join'] : '');
        }
        $sql .= $this->parseWhere(!empty($options['where']) ? $options['where'] : '');
        if (!strpos($table, ',')) {
            //  单表更新支持order和lmit
            $sql .= $this->parseOrder(!empty($options['order']) ? $options['order'] : '')
                . $this->parseLimit(!empty($options['limit']) ? $options['limit'] : '');
        }
        $sql .= $this->parseComment(!empty($options['comment']) ? $options['comment'] : '');
        return $this->execute($sql, !empty($options['fetch_sql']));
    }

    // where子单元分析

    /**
     * set分析
     * @access protected
     * @param array $data
     * @return string
     */
    protected function parseSet($data)
    {
        $set = [];
        foreach ($data as $key => $val) {
            if (is_array($val) && 'exp' == $val[0]) {
                $set[] = $this->parseKey($key) . '=' . $val[1];
            } elseif (is_null($val)) {
                $set[] = $this->parseKey($key) . '=NULL';
            } elseif (is_scalar($val)) {// 过滤非标量数据
                if (0 === strpos($val, ':') && in_array($val, array_keys($this->bind))) {
                    $set[] = $this->parseKey($key) . '=' . $this->escapeString($val);
                } else {
                    $name = count($this->bind);
                    $set[] = $this->parseKey($key) . '=:' . $name;
                    $this->bindParam($name, $val);
                }
            }
        }
        return ' SET ' . implode(',', $set);
    }

    /**
     * 删除记录
     * @access public
     * @param array $options 表达式
     * @return false | integer
     */
    public function delete($options = [])
    {
        $this->model = $options['model'];
        $this->parseBind(!empty($options['bind']) ? $options['bind'] : []);
        $table = $this->parseTable($options['table']);
        $sql = 'DELETE FROM ' . $table;
        if (strpos($table, ',')) {// 多表删除支持USING和JOIN操作
            if (!empty($options['using'])) {
                $sql .= ' USING ' . $this->parseTable($options['using']) . ' ';
            }
            $sql .= $this->parseJoin(!empty($options['join']) ? $options['join'] : '');
        }
        $sql .= $this->parseWhere(!empty($options['where']) ? $options['where'] : '');
        if (!strpos($table, ',')) {
            // 单表删除支持order和limit
            $sql .= $this->parseOrder(!empty($options['order']) ? $options['order'] : '')
                . $this->parseLimit(!empty($options['limit']) ? $options['limit'] : '');
        }
        $sql .= $this->parseComment(!empty($options['comment']) ? $options['comment'] : '');
        return $this->execute($sql, !empty($options['fetch_sql']));
    }

    /**
     * 查找记录
     * @access public
     * @param array $options 表达式
     * @return mixed
     */
    public function select($options = [])
    {
        $this->model = $options['model'];
        $this->parseBind(!empty($options['bind']) ? $options['bind'] : []);
        $sql = $this->buildSelectSql($options);
        return $this->query($sql, !empty($options['fetch_sql']));
    }

    /**
     * 执行查询 返回数据集
     * @access public
     * @param string $str sql指令
     * @param boolean $fetchSql 不执行只是获取SQL
     * @param bool $fetchOne
     * @return bool|array
     */
    public function &query(string $str, $fetchSql = false, $fetchOne = false)
    {
        if ($fetchSql) {
            return $str;
        }

        $result = false;

        $this->queryStr = $str;
        $this->initConnect(false);
        if (!$this->_linkID) {
            return $result;
        }
        //释放前次的查询结果
        if (!empty($this->PDOStatement)) $this->free();
        ++$this->queryTimes;

        // 调试开始
        $this->debug(true);
        $this->PDOStatement = $this->_linkID->prepare($str);
        if (false === $this->PDOStatement) {
            echo 'fjdskjaslfdk';
            $this->error();
            return $result;
        }
        foreach ($this->bind as $key => $val) {
            if (is_array($val)) {
                $this->PDOStatement->bindValue($key, $val[0], $val[1]);
            } else {
                $this->PDOStatement->bindValue($key, $val);
            }
        }
        $this->bind = [];
        $result = $this->PDOStatement->execute();
        // 调试结束
        $this->debug(false);

        if (false === $result) {
            echo 'fjdskjaslfdk';
            $this->error();
            return $result;
        } else {
            return $this->getResult($str, $fetchOne);
        }
    }

    /**
     * 获得所有的查询数据
     * @access private
     * @param string $key
     * @param bool $fetchOne
     * @return array
     */
    private function &getResult(string $key, $fetchOne = false)
    {
        if ($fetchOne) {
            $result = '';
            $row = $this->PDOStatement->fetch(PDO::FETCH_NUM);
            if (!empty($row)) {
                $result = $row[0];
            }
            return $result;
        } else {
            //返回数据集
            $result = $this->PDOStatement->fetchAll(PDO::FETCH_ASSOC);
            $this->numRows = count($result);
            return $result;
        }
    }

    /**
     * 获取最近一次查询的sql语句
     * @param string $model 模型名
     * @access public
     * @return string
     */
    public function getLastSql($model = '')
    {
        return $model ? $this->modelSql[$model] : $this->queryStr;
    }

    /**
     * 获取最近插入的ID
     * @access public
     * @return string
     */
    public function getLastInsID()
    {
        return $this->lastInsID;
    }

    /**
     * 获取最近的错误信息
     * @access public
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * 设置当前操作模型
     * @access public
     * @param string $model 模型名
     * @return void
     */
    public function setModel(string $model)
    {
        $this->model = $model;
    }

    public function getRowCount()
    {
        return $this->numRows;
    }

    /**
     * 析构方法
     * @access public
     */
    public function __destruct()
    {
        // 释放查询
        if ($this->PDOStatement) {
            $this->free();
        }
        // 关闭连接
        $this->close();
    }

    /**
     * 关闭数据库
     * @access public
     */
    public function close()
    {
        $this->_linkID = null;
    }

    /**
     * union分析
     * @access protected
     * @param mixed $union
     * @return string
     */
    protected function parseUnion($union)
    {
        if (empty($union)) return '';
        if (isset($union['_all'])) {
            $str = 'UNION ALL ';
            unset($union['_all']);
        } else {
            $str = 'UNION ';
        }
        $sql = [];
        foreach ($union as $u) {
            $sql[] = $str . (is_array($u) ? $this->buildSelectSql($u) : $u);
        }
        return implode(' ', $sql);
    }

    /**
     * 生成查询SQL
     * @access public
     * @param array $options 表达式
     * @return string
     */
    public function buildSelectSql($options = [])
    {
        if (isset($options['page'])) {
            // 根据页数计算limit
            list($page, $listRows) = $options['page'];
            $page = $page > 0 ? $page : 1;
            $listRows = $listRows > 0 ? $listRows : (is_numeric($options['limit']) ? $options['limit'] : 20);
            $offset = $listRows * ($page - 1);
            $options['limit'] = $offset . ',' . $listRows;
        }
        return $this->parseSql($this->selectSql, $options);
    }

    /**
     * 替换SQL语句中表达式
     * @access public
     * @param $sql
     * @param array $options 表达式
     * @return string
     */
    public function parseSql($sql, $options = [])
    {
        $sql = str_replace(
            ['%TABLE%', '%DISTINCT%', '%FIELD%', '%JOIN%', '%WHERE%', '%GROUP%', '%HAVING%', '%ORDER%', '%LIMIT%', '%UNION%', '%LOCK%', '%COMMENT%', '%FORCE%'],
            [
                $this->parseTable($options['table']),
                $this->parseDistinct(isset($options['distinct']) ? $options['distinct'] : false),
                $this->parseField(!empty($options['field']) ? $options['field'] : '*'),
                $this->parseJoin(!empty($options['join']) ? $options['join'] : ''),
                $this->parseWhere(!empty($options['where']) ? $options['where'] : ''),
                $this->parseGroup(!empty($options['group']) ? $options['group'] : ''),
                $this->parseHaving(!empty($options['having']) ? $options['having'] : ''),
                $this->parseOrder(!empty($options['order']) ? $options['order'] : ''),
                $this->parseLimit(!empty($options['limit']) ? $options['limit'] : ''),
                $this->parseUnion(!empty($options['union']) ? $options['union'] : ''),
                $this->parseLock(isset($options['lock']) ? $options['lock'] : false),
                $this->parseComment(!empty($options['comment']) ? $options['comment'] : ''),
                $this->parseForce(!empty($options['force']) ? $options['force'] : '')
            ], $sql);
        return $sql;
    }

    /**
     * table分析
     * @access protected
     * @param $tables
     * @return string
     */
    protected function parseTable($tables)
    {
        if (is_array($tables)) {// 支持别名定义
            $array = [];
            foreach ($tables as $table => $alias) {
                if (!is_numeric($table))
                    $array[] = $this->parseKey($table) . ' ' . $this->parseKey($alias);
                else
                    $array[] = $this->parseKey($alias);
            }
            $tables = $array;
        } elseif (is_string($tables)) {
            $tables = explode(',', $tables);
            array_walk($tables, [&$this, 'parseKey']);
        }
        return implode(',', $tables);
    }

    /**
     * distinct分析
     * @access protected
     * @param mixed $distinct
     * @return string
     */
    protected function parseDistinct($distinct)
    {
        return !empty($distinct) ? ' DISTINCT ' : '';
    }

    /**
     * field分析
     * @access protected
     * @param mixed $fields
     * @return string
     */
    protected function parseField($fields)
    {
        if (is_string($fields) && '' !== $fields) {
            $fields = explode(',', $fields);
        }
        if (is_array($fields)) {
            // 完善数组方式传字段名的支持
            // 支持 'field1'=>'field2' 这样的字段别名定义
            $array = [];
            foreach ($fields as $key => $field) {
                if (!is_numeric($key))
                    $array[] = $this->parseKey($key) . ' AS ' . $this->parseKey($field);
                else
                    $array[] = $this->parseKey($field);
            }
            $fieldsStr = implode(',', $array);
        } else {
            $fieldsStr = '*';
        }
        //TODO 如果是查询全部字段，并且是join的方式，那么就把要查的表加个别名，以免字段被覆盖
        return $fieldsStr;
    }

    /**
     * 字段名分析
     * @access protected
     * @param string $key
     * @return string
     */
    protected function parseKey(string $key)
    {
        return $key;
    }

    /**
     * join分析
     * @access protected
     * @param mixed $join
     * @return string
     */
    protected function parseJoin($join)
    {
        $joinStr = '';
        if (!empty($join)) {
            $joinStr = ' ' . implode(' ', $join) . ' ';
        }
        return $joinStr;
    }

    /**
     * where分析
     * @access protected
     * @param mixed $where
     * @return string
     */
    protected function parseWhere($where)
    {
        $whereStr = '';
        if (is_string($where)) {
            // 直接使用字符串条件
            $whereStr = $where;
        } else { // 使用数组表达式
            $operate = isset($where['_logic']) ? strtoupper($where['_logic']) : '';
            if (in_array($operate, ['AND', 'OR', 'XOR'])) {
                // 定义逻辑运算规则 例如 OR XOR AND NOT
                $operate = ' ' . $operate . ' ';
                unset($where['_logic']);
            } else {
                // 默认进行 AND 运算
                $operate = ' AND ';
            }
            foreach ($where as $key => $val) {
                if (is_numeric($key)) {
                    $key = '_complex';
                }
                if (0 === strpos($key, '_')) {
                    // 解析特殊条件表达式
                    $whereStr .= $this->parseThinkWhere($key, $val);
                } else {
                    // 查询字段的安全过滤
                    // if(!preg_match('/^[A-Z_\|\&\-.a-z0-9\(\)\,]+$/',trim($key))){
                    //     E(L('_EXPRESS_ERROR_').':'.$key);
                    // }
                    // 多条件支持
                    $multi = is_array($val) && isset($val['_multi']);
                    $key = trim($key);
                    if (strpos($key, '|')) { // 支持 name|title|nickname 方式定义查询字段
                        $array = explode('|', $key);
                        $str = [];
                        foreach ($array as $m => $k) {
                            $v = $multi ? $val[$m] : $val;
                            $str[] = $this->parseWhereItem($this->parseKey($k), $v);
                        }
                        $whereStr .= '( ' . implode(' OR ', $str) . ' )';
                    } elseif (strpos($key, '&')) {
                        $array = explode('&', $key);
                        $str = [];
                        foreach ($array as $m => $k) {
                            $v = $multi ? $val[$m] : $val;
                            $str[] = '(' . $this->parseWhereItem($this->parseKey($k), $v) . ')';
                        }
                        $whereStr .= '( ' . implode(' AND ', $str) . ' )';
                    } else {
                        $whereStr .= $this->parseWhereItem($this->parseKey($key), $val);
                    }
                }
                $whereStr .= $operate;
            }
            $whereStr = substr($whereStr, 0, -strlen($operate));
        }
        return empty($whereStr) ? '' : ' WHERE ' . $whereStr;
    }

    /**
     * 特殊条件分析
     * @access protected
     * @param string $key
     * @param mixed $val
     * @return string
     */
    protected function parseThinkWhere(string $key, $val)
    {
        $whereStr = '';
        switch ($key) {
            case '_string':
                // 字符串模式查询条件
                $whereStr = $val;
                break;
            case '_complex':
                // 复合查询条件
                $whereStr = substr($this->parseWhere($val), 6);
                break;
            case '_query':
                // 字符串模式查询条件
                parse_str($val, $where);
                if (isset($where['_logic'])) {
                    $op = ' ' . strtoupper($where['_logic']) . ' ';
                    unset($where['_logic']);
                } else {
                    $op = ' AND ';
                }
                $array = [];
                foreach ($where as $field => $data)
                    $array[] = $this->parseKey($field) . ' = ' . $this->parseValue($data);
                $whereStr = implode($op, $array);
                break;
        }
        return '( ' . $whereStr . ' )';
    }

    /**
     * value分析
     * @access protected
     * @param mixed $value
     * @return string|array
     */
    protected function parseValue($value)
    {
        if (is_string($value)) {
            $value = strpos($value, ':') === 0 && in_array($value, array_keys($this->bind)) ? $this->escapeString($value) : '\'' . $this->escapeString($value) . '\'';
        } elseif (isset($value[0]) && is_string($value[0]) && strtolower($value[0]) == 'exp') {
            $value = $this->escapeString($value[1]);
        } elseif (is_array($value)) {
            $value = array_map([$this, 'parseValue'], $value);
        } elseif (is_bool($value)) {
            $value = $value ? '1' : '0';
        } elseif (is_null($value)) {
            $value = 'null';
        }
        return $value;
    }

    /**
     * SQL指令安全过滤
     * @access public
     * @param string $str SQL字符串
     * @return string
     */
    public function escapeString(string $str)
    {
        return addslashes($str);
    }

    /**
     * @param $key
     * @param $val
     * @return string
     */
    protected function parseWhereItem($key, $val)
    {
        $whereStr = '';
        if (is_array($val)) {
            if (is_string($val[0])) {
                $exp = strtolower($val[0]);
                if (preg_match('/^(eq|neq|gt|egt|lt|elt)$/', $exp)) { // 比较运算
                    $whereStr .= $key . ' ' . $this->exp[$exp] . ' ' . $this->parseValue($val[1]);
                } elseif (preg_match('/^(notlike|like)$/', $exp)) {// 模糊查找
                    if (is_array($val[1])) {
                        $likeLogic = isset($val[2]) ? strtoupper($val[2]) : 'OR';
                        if (in_array($likeLogic, ['AND', 'OR', 'XOR'])) {
                            $like = [];
                            foreach ($val[1] as $item) {
                                $like[] = $key . ' ' . $this->exp[$exp] . ' ' . $this->parseValue($item);
                            }
                            $whereStr .= '(' . implode(' ' . $likeLogic . ' ', $like) . ')';
                        }
                    } else {
                        $whereStr .= $key . ' ' . $this->exp[$exp] . ' ' . $this->parseValue($val[1]);
                    }
                } elseif ('bind' == $exp) { // 使用表达式
                    $whereStr .= $key . ' = :' . $val[1];
                } elseif ('exp' == $exp) { // 使用表达式
                    $whereStr .= $key . ' ' . $val[1];
                } elseif (preg_match('/^(notin|not in|in)$/', $exp)) { // IN 运算
                    if (isset($val[2]) && 'exp' == $val[2]) {
                        $whereStr .= $key . ' ' . $this->exp[$exp] . ' ' . $val[1];
                    } else {
                        if (is_string($val[1])) {
                            $val[1] = explode(',', $val[1]);
                        }
                        $zone = implode(',', $this->parseValue($val[1]));
                        $whereStr .= $key . ' ' . $this->exp[$exp] . ' (' . $zone . ')';
                    }
                } elseif (preg_match('/^(notbetween|not between|between)$/', $exp)) { // BETWEEN运算
                    $data = is_string($val[1]) ? explode(',', $val[1]) : $val[1];
                    $whereStr .= $key . ' ' . $this->exp[$exp] . ' ' . $this->parseValue($data[0]) . ' AND ' . $this->parseValue($data[1]);
                } else {
                }
            } else {
                $count = count($val);
                $rule = isset($val[$count - 1]) ? (is_array($val[$count - 1]) ? strtoupper($val[$count - 1][0]) : strtoupper($val[$count - 1])) : '';
                if (in_array($rule, ['AND', 'OR', 'XOR'])) {
                    $count = $count - 1;
                } else {
                    $rule = 'AND';
                }
                for ($i = 0; $i < $count; ++$i) {
                    $data = is_array($val[$i]) ? $val[$i][1] : $val[$i];
                    if ('exp' == strtolower($val[$i][0])) {
                        $whereStr .= $key . ' ' . $data . ' ' . $rule . ' ';
                    } else {
                        $whereStr .= $this->parseWhereItem($key, $val[$i]) . ' ' . $rule . ' ';
                    }
                }
                $whereStr = '( ' . substr($whereStr, 0, -4) . ' )';
            }
        } else {
            //对字符串类型字段采用模糊匹配
            $likeFields = $this->config['db_like_fields'];
            if ($likeFields && preg_match('/^(' . $likeFields . ')$/i', $key)) {
                $whereStr .= $key . ' LIKE ' . $this->parseValue('%' . $val . '%');
            } else {
                $whereStr .= $key . ' = ' . $this->parseValue($val);
            }
        }
        return $whereStr;
    }

    /**
     * group分析
     * @access protected
     * @param mixed $group
     * @return string
     */
    protected function parseGroup($group)
    {
        return !empty($group) ? ' GROUP BY ' . $group : '';
    }

    /**
     * having分析
     * @access protected
     * @param string $having
     * @return string
     */
    protected function parseHaving(string $having)
    {
        return !empty($having) ? ' HAVING ' . $having : '';
    }

    /**
     * order分析
     * @access protected
     * @param mixed $order
     * @return string
     */
    protected function parseOrder($order)
    {
        if (is_array($order)) {
            $array = [];
            foreach ($order as $key => $val) {
                if (is_numeric($key)) {
                    $array[] = $this->parseKey($val);
                } else {
                    $array[] = $this->parseKey($key) . ' ' . $val;
                }
            }
            $order = implode(',', $array);
        }
        return !empty($order) ? ' ORDER BY ' . $order : '';
    }

    /**
     * limit分析
     * @access protected
     * @param $limit
     * @return string
     * @internal param mixed $lmit
     */
    protected function parseLimit($limit)
    {
        return !empty($limit) ? ' LIMIT ' . $limit . ' ' : '';
    }

    /**
     * 设置锁机制
     * @access protected
     * @param bool $lock
     * @return string
     */
    protected function parseLock($lock = false)
    {
        return $lock ? ' FOR UPDATE ' : '';
    }

    /**
     * comment分析
     * @access protected
     * @param string $comment
     * @return string
     */
    protected function parseComment(string $comment)
    {
        return !empty($comment) ? ' /* ' . $comment . ' */' : '';
    }

    /**
     * index分析，可在操作链中指定需要强制使用的索引
     * @access protected
     * @param mixed $index
     * @return string
     */
    protected function parseForce($index)
    {
        if (empty($index)) return '';
        if (is_array($index)) $index = join(",", $index);
        return sprintf(" FORCE INDEX ( %s ) ", $index);
    }
}
