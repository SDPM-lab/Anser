<?php

namespace SDPMlab\Anser\Orchestration\Saga\Cache\Redis;

use SDPMlab\Anser\Orchestration\Saga\Cache\BaseCacheHandler;
use SDPMlab\Anser\Orchestration\Saga\Cache\CacheHandlerInterface;
use Predis\Client;
use Predis\ClientException;

class RedisHandler extends BaseCacheHandler
{
    protected $config = [
        'scheme'   => 'tcp',
        'host'     => '127.0.0.1',
        'port'     => 6379,
        'timeout'  => 0,
    ];

    protected $option = [];

    protected $path = '';

    /**
     * The client of Redis.
     *
     * @var client
     */
    protected $client;

    public function __construct($config = null, $option = null)
    {
        parent::__construct();
        
        try {
            if (is_string($config)) {
                $configArr = explode(':', $config);

                $this->config["scheme"] = $configArr[0];
                $this->config["host"]   = str_replace("/", "", $configArr[1]);
                $this->config["port"]   = $configArr[2];
            }

            if (is_array($config)) {
                $this->config = $config;
            }

            if (!is_null($option)) {
                $this->option = $option;
                $this->client = new Client($config ?? $this->config, $option);
            } else {
                $this->client = new Client($config ?? $this->config);
            }

            return $this;
        } catch (ClientException $e) {
            throw new ClientException($e->getMessage());
        }
    }

    public function initOrchestrator(string $orchestratorNumber): CacheHandlerInterface
    {
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
