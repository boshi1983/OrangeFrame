<?php

class MappingRedisSaver implements iMappingSaver
{
    const MAPPING_KEY = 'mapping_key';

    /**
     * @var RedisClient
     */
    private $redisClient;

    /**
     * @param BaseServer $server
     */
    public function __construct($server)
    {
        $this->redisClient = $server->get('RedisModel');
    }

    function read()
    {
        $redis = $this->redisClient->getRedis();
        return json_decode($redis->get(self::MAPPING_KEY), true);
    }

    function write($data)
    {
        $redis = $this->redisClient->getRedis();
        $redis->set(self::MAPPING_KEY, json_encode($data));
    }

}