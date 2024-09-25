<?php

namespace Insead\MIMBundle\Service\Redis;

use Psr\Log\LoggerInterface;

use Redis;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class Base
{
    protected $logger;
    protected $env;
    protected $redis;
    protected $redisPrefix;
    protected $logUuid;
    protected string $redis_host;
    protected string $redis_port;

    public function __construct( ParameterBagInterface $parameterBag, LoggerInterface $logger)
    {
        $config                     = $parameterBag->get('redis.config');
        $this->logger               = $logger;
        $this->redis_host           = $config['redis_host'];
        $this->redis_port           = $config['redis_port'];
        $this->env                  = $config['symfony_environment'];

        $this->redisPrefix          = "study:" . $this->env . ":";

        $this->redis = new Redis();
        $this->redis->connect($config['redis_host'], $config['redis_port']);
        $this->redis->setOption(Redis::OPT_PREFIX, $this->redisPrefix);
    }

    /**
     * @return mixed
     */
    public function getRedisHost(): string
    {
        return $this->redis_host;
    }

    /**
     * @return mixed
     */
    public function getRedisPort(): string
    {
        return $this->redis_port;
    }

    /**
     * Function that logs a message, prefixing the Class and function name to help debug
     *
     * @param String $msg Message to be logged
     *
     **/
    protected function log($msg)
    {
        $matches = [];

        preg_match('/[^\\\\]+$/', static::class, $matches);

        if( $this->logUuid ) {
            $this->logger->info(
                $this->logUuid
                . " Redis Service: "
                . $matches[0]
                . ":"
                . debug_backtrace()[1]['function']
                . " - "
                . $msg
            );
        } else {
            $this->logger->info(
                'Redis Service: '
                . $matches[0]
                . ":"
                . debug_backtrace()[1]['function']
                . " - "
                . $msg);
        }
    }

    /**
     * Function to get the value of the given key with the preset PREFIX
     * @param String $key redis key to be searched
     **
     * @return String
     */
    public function get($key) {
        $this->log('RETRIEVING VALUE for ' . $this->redisPrefix . $key);

        $value = $this->redis->get($key);

        return $value;
    }

    /**
     * Function to get the value of the given key EXACT MATCH
     * @param String $key redis key to be searched
     **
     * @return String
     */
    public function getExact($key) {
        $this->log('RETRIEVING VALUE for ' . $key);

        $this->redis->setOption(Redis::OPT_PREFIX, "");

        $value = $this->redis->get($key);

        $this->redis->setOption(Redis::OPT_PREFIX, $this->redisPrefix);

        return $value;
    }

    /**
     * Function to set the value of the given key with the preset PREFIX
     * @param String $key redis key to be searched
     * @param String $value value to be saved
     * @param int $ttl number of seconds until this key would auto expire;
     **
     * @return String
     */
    public function set($key,$value,$ttl=-1) {
        $this->log('SETTING VALUE for ' . $this->redisPrefix. $key);

        $newValue = "";
        if( $ttl > 0 ) {
            if( $this->redis->set($key,$value,$ttl) ) {
                $newValue = $this->redis->get($key);
            }
        } else {
            if( $this->redis->set($key,$value) ) {
                $newValue = $this->redis->get($key);
            }
        }

        return $newValue;
    }

    /**
     * Function to set the value of the given key EXACT MATCH
     * @param String $key redis key to be searched
     * @param String $value value to be saved
     * @param int $ttl number of seconds until this key would auto expire;
     **
     * @return String
     */
    public function setExact($key,$value,$ttl=-1) {
        $this->log('SETTING VALUE for ' . $key);

        $this->redis->setOption(Redis::OPT_PREFIX, "");

        $newValue = "";
        if( $ttl > 0 ) {
            if( $this->redis->set($key,$value,$ttl) ) {
                $newValue = $this->redis->get($key);
            }
        } else {
            if( $this->redis->set($key,$value) ) {
                $newValue = $this->redis->get($key);
            }
        }

        $this->redis->setOption(Redis::OPT_PREFIX, $this->redisPrefix);

        return $newValue;
    }

    public function setLogUuid($logUuid) {
        $this->logUuid = $logUuid;
    }

}
