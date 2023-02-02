<?php

namespace SDPMlab\Anser\Orchestration\Saga\Cache\Redis;

use SDPMlab\Anser\Orchestration\Saga\Cache\BaseCacheHandler;
use SDPMlab\Anser\Orchestration\Saga\Cache\CacheHandlerInterface;
use SDPMlab\Anser\Orchestration\OrchestratorInterface;
use SDPMlab\Anser\Exception\RedisException;
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

    /**
     * The key(number) of serialized orchestrator.
     *
     * @var string
     */
    protected $orchestratorNumber;

    /**
     * The serialized orchestrator.
     *
     * @var string
     */
    protected $serializedOrchestrator;

    public function __construct($config = null, $option = null)
    {
        parent::__construct();

        try {
            if (is_string($config)) {
                $configArr = explode(':', $config);

                if (count($configArr) !== 3) {
                    throw RedisException::forCacheFormatError();
                }

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

    public function initOrchestrator(string $orchestratorNumber, OrchestratorInterface $runtimeOrchestrator): CacheHandlerInterface
    {
        $this->orchestratorNumber = $orchestratorNumber;

        if ($this->client->exists($orchestratorNumber) === 1) {
            throw RedisException::forCacheRepeatOrch($orchestratorNumber);
        }

        $serializedOrchestrator = $this->serializeOrchestrator($runtimeOrchestrator);

        $this->client->set($this->orchestratorNumber, $serializedOrchestrator);

        return $this;
    }

    public function setOrchestrator(OrchestratorInterface $runtimeOrchestrator): CacheHandlerInterface
    {
        if ($this->client->exists($this->orchestratorNumber) === 0) {
            throw RedisException::forCacheOrchestratorNumberNotFound($this->orchestratorNumber);
        }

        $this->client->set($this->orchestratorNumber, $this->serializeOrchestrator($runtimeOrchestrator));

        return $this;
    }

    public function getOrchestrator(): OrchestratorInterface
    {
        if ($this->client->exists($this->orchestratorNumber) === 0) {
            throw RedisException::forCacheOrchestratorNumberNotFound($this->orchestratorNumber);
        }

        $runtimeOrchestrator = $this->unserializeOrchestrator($this->client->get($this->orchestratorNumber));

        return $runtimeOrchestrator;
    }

    public function clearOrchestrator(): bool
    {
        if ($this->client->exists($this->orchestratorNumber) === 0) {
            throw RedisException::forCacheOrchestratorNumberNotFound($this->orchestratorNumber);
        }

        return $this->client->del($this->orchestratorNumber);
    }
}
