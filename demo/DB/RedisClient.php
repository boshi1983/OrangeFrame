<?php
/**
 * Created by PhpStorm.
 * User: caobo
 * Date: 15/9/3
 * Time: 下午3:20
 */

/**
 * Class RedisClient
 */
class RedisClient
{
    /**
     * @var RedisClient
     */
    public static $instance = NULL;
    /**
     * @var Redis
     */
    protected $_curRedis;
    /**
     * @var array
     */
    private $linkHandle = [];

    /** construct:connect redis
     * RedisClient constructor.
     * @param $configs
     */
    public function __construct($configs)
    {
        $this->initRedis($configs);
    }

    /**
     * 初始化Redis
     * @param $conf
     */
    private function initRedis($conf)
    {
        $master = $conf[DemoDefine::REDIS_MASTER];
        $key = mt_rand(1, count($master)) - 1;
        $v = $master[$key];
        $obj = new Redis();
        if ($obj->pconnect($v['host'], $v['port'])) {
            $obj->auth($v['auth']);
            $this->linkHandle[DemoDefine::REDIS_MASTER] = $obj;
        }

        if (isset($conf[DemoDefine::REDIS_SLAVE])) {
            $slave = $conf[DemoDefine::REDIS_SLAVE];
            if (empty($slave) == false) {
                $key = mt_rand(1, count($slave)) - 1;
                $v = $slave[$key];
                $obj = new Redis();
                if ($obj->pconnect($v['host'], $v['port'])) {
                    $obj->auth($v['auth']);
                    $this->linkHandle[DemoDefine::REDIS_SLAVE] = $obj;
                }
            }
        }
    }

    /**
     * Get a instance of MyRedisClient
     *
     * @param $configs
     * @return object
     * @internal param string $key
     */
    static function getInstance($configs)
    {
        if (!self::$instance) {
            self::$instance = new self($configs);
        }
        return self::$instance;
    }

    /**
     * 获得redis Resources
     *
     * @param string $tag master/slave
     * @return Redis
     */
    public function &getRedis($tag = DemoDefine::REDIS_MASTER)
    {
        if (isset($this->linkHandle[$tag])) {
            $this->_curRedis = &$this->linkHandle[$tag];
            return $this->_curRedis;
        }

        $this->_curRedis = &$this->linkHandle[DemoDefine::REDIS_MASTER];
        return $this->_curRedis;
    }

    /**
     * @return Redis
     */
    public function &getCurrentRedis()
    {
        return $this->_curRedis;
    }

    /**
     * 关闭连接
     * pconnect 连接是无法关闭的
     *
     * @param int $flag 关闭选择 0:关闭 Master 1:关闭 Slave 2:关闭所有
     * @return boolean
     */
    public function close($flag = 2)
    {
        switch ($flag) {
            // 关闭 Master
            case 0:
                {
                    if (isset($this->linkHandle[DemoDefine::REDIS_MASTER]))
                        $this->linkHandle[DemoDefine::REDIS_MASTER]->close();
                }
                break;
            // 关闭 Slave
            case 1:
                {
                    if (isset($this->linkHandle[DemoDefine::REDIS_SLAVE]))
                        $this->linkHandle[DemoDefine::REDIS_SLAVE]->close();
                }
                break;
            // 关闭所有
            default:
                {
                    $this->close(0);
                    $this->close(1);
                }
                break;
        }
        return true;
    }
}
