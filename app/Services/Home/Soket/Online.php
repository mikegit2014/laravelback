<?php

namespace App\Services\Home\Soket;

use Exception;
use App\Services\Home\Consts\RedisKey;

/**
 * 处理博客在线人数的问题
 *
 * @author jiang <mylampblog@163.com>
 */
class Online
{
    /**
     * redis 操作对象
     * @var object
     */
    private $redisClient;

    /**
     * 相关的配置
     * @var [type]
     */
    private $config;

    /**
     * 初始化配置和redis服务连接对象
     */
    public function __construct($config)
    {
        $this->config = $config;
        $this->initRedisServer();
    }

    /**
     * 开始统计
     */
    public function count($fd, $params, $onType)
    {
        if(method_exists($this, $onType)) {
            return $this->$onType($fd, $params);
        }
    }

    /**
     * 当来消息的时候，这里一般为新用户访问的时候
     */
    private function onMessage($fd, $params)
    {
        if( ! isset($params['uuid'])) return false;
        try {
            $this->redisClient->setex(RedisKey::ONLINE_MEMBER_UUID_PREFIX . $params['uuid'], $this->config['online_ttl'], 1);
            $this->redisClient->setex(RedisKey::ONLINE_MEMBER_FD_PREFIX . $fd, $this->config['online_ttl'], 1);
            $count = $this->recount();
            $nums = $count['nums'];
            $fdList = $count['fdList'];
        } catch (Exception $e) {
            $nums = 0;
            $fdList = [];
        }
        return compact('nums', 'fdList');
    }

    /**
     * 当用户离开的时候
     */
    private function onClose($fd, $params)
    {
        try {
            $this->redisClient->del(RedisKey::ONLINE_MEMBER_FD_PREFIX . $fd);
            $count = $this->recount();
            $nums = $count['nums'];
            $fdList = $count['fdList'];
        } 
        catch (Exception $e) {
            $nums = 0;
            $fdList = [];
        }
        return compact('nums', 'fdList');
    }

    /**
     * 无论什么情况，都重新统计一次在线人数，并返回给客户端
     */
    private function recount()
    {
        $uuidList = $this->redisClient->keys(RedisKey::ONLINE_MEMBER_UUID_PREFIX . '*');
        $fdList = $this->redisClient->keys(RedisKey::ONLINE_MEMBER_FD_PREFIX . '*');
        $fds = array_map(function($fd) {
            return str_replace(RedisKey::ONLINE_MEMBER_FD_PREFIX, '', $fd);
        }, $fdList);
        $fdList = $fds;
        $nums = count($uuidList);
        return compact('nums', 'fdList');
    }

    /**
     * redis 连接对象
     */
    private function initRedisServer()
    {
        $server = array(
            'host'     => $this->config['redis']['default']['host'],
            'port'     => $this->config['redis']['default']['port'],
            'database' => $this->config['redis']['default']['database'],
        );
        $this->redisClient = new \Predis\Client($server);
    }

}