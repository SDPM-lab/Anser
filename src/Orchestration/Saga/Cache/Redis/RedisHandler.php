<?php

namespace SDPMlab\Anser\Orchestration\Saga\Cache\Redis;

use SDPMlab\Anser\Orchestration\Saga\Cache\BaseCacheHandler;
use SDPMlab\Anser\Orchestration\Saga\Cache\CacheHandlerInterface;

use Redis;
use Exception;
use RedisException;

class RedisHandler extends BaseCacheHandler
{

    protected $config = [
        'host'     => '127.0.0.1',
        'password' => null,
        'port'     => 6379,
        'timeout'  => 0,
        'database' => 0,
    ];

    protected $option = [];

    /**
     * Redis conntion.
     *
     * @var Redis
     */
    protected $redis;

    public function __construct(BaseCacheHandler $baseCacheHandler)
    {
        $this->config = $baseCacheHandler->config;
        $this->option = $baseCacheHandler->option;
    }

    public function __destruct()
    {
        if (isset($this->redis)) {
            $this->redis->close();
        }
    }

    public function initCacheDriver()
    {
        $this->redis = new Redis();

        try {
            if (!$this->redis->connect($this->config["hostname"], $this->config["port"])) {
                throw new Exception("Redis connection failed.");
            }

            if (!$this->redis->auth($this->config["password"])) {
                throw new Exception("Redis authentication failed.");
            }

            if (!$this->redis->select($this->config["database"])) {
                throw new Exception("Redis select database failed.");
            }
        } catch (RedisException $e) {
            throw new Exception('RedisException occurred with message (' . $e->getMessage() . ').');
        }
    }

    public function initOrchestrator(string $orchestratorNumber): CacheHandlerInterface {
        return $this;
    }

    public function setOrchestratorStatus(string $orchestratorNumber, string $orchestratorStatus): CacheHandlerInterface
    {
        return $this;
    }

    public function getOrchestratorStatus(string $orchestratorNumber): string
    {
        $status = "";

        return $status;
    }
}