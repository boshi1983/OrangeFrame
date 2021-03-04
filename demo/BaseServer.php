<?php


class BaseServer extends Container
{
    /**
     * BaseServer constructor.
     */
    public function __construct()
    {
        parent:: __construct();
        $this->init();
    }

    public function __destruct()
    {
    }

    protected function init()
    {
        $this->connectMysql();
        $this->connectRedis();
        $this->set('server', $this);
    }

    protected function connectMysql()
    {
        $config = [
            'type' => '',// 数据库类型
            'hostname' => MYSQL_MASTER_HOST,                // 服务器地址 ,
            'database' => MYSQL_MASTER_DBNAME,              // 数据库名 ,
            'username' => MYSQL_MASTER_USERNAME,            // 用户名 ,
            'password' => MYSQL_MASTER_PASSWORD,            // 密码 ,
            'hostport' => MYSQL_MASTER_PORT,                // 端口 ,
            'dsn' => '',             // ,
            'charset' => MYSQL_MASTER_CHARSET,         // 数据库编码默认采用utf8 ,
            'params' => [],    // 数据库连接参数
            'prefix' => '',         // 数据库表前缀
            'debug' => false,      // 数据库调试模式
            'deploy' => 0,          // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
            'rw_separate' => false,      // 数据库读写是否分离 主从式有效
            'master_num' => 1,          // 读写分离后 主服务器数量
            'slave_no' => '',         // 指定从服务器序号
            'db_like_fields' => '',
        ];

        $mysql = $this->get('Mysql');
        $mysql->setConfig($config);
    }

    protected function connectRedis()
    {
        $config = [
            DemoDefine::REDIS_MASTER => [
                ['host' => REDIS_MASTER_HOST, 'port' => REDIS_MASTER_PORT, 'auth' => REDIS_MASTER_PW],
            ],
            //GameDefine::REDIS_SLAVE => array(
            //    array('host'=>REDIS_SLAVE_HOST, 'port'=>REDIS_SLAVE_PORT, 'auth'=>REDIS_SLAVE_PW),
            //),
        ];

        $redisHandler = new RedisClient($config);
        $redis = $redisHandler->getRedis(DemoDefine::REDIS_MASTER);
        if (!empty($redis)) {
            $this->set('RedisModel', $redisHandler);
        }
    }
}