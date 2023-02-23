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
     * The serverName as the hashmap key.
     *
     * @var string|null
     */
    protected $serverName = null;

    /**
     * The key(number) of serialized orchestrator.
     *
     * @var string|null
     */
    protected $orchestratorNumber = null;

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

    /**
     * {@inheritDoc}
     */
    public function initOrchestrator(string $serverName, string $orchestratorNumber, OrchestratorInterface $runtimeOrchestrator): CacheHandlerInterface
    {
        $this->orchestratorNumber = $orchestratorNumber;

        $this->serverName = $serverName;

        if (!is_null($this->client->hget($serverName, $orchestratorNumber))) {
            throw RedisException::forCacheRepeatOrch($orchestratorNumber);
        }

        $serializedOrchestrator = $this->serializeOrchestrator($runtimeOrchestrator);

        // If the serverName isn't exist in hashmap, add it to serverNameList set.
        if (empty($this->client->hgetall($serverName))) {
            $this->client->sadd("serverNameList", $serverName);
        }

        // Set orchNumber and serialized runtimeOrch in the serverName hashmap.
        $this->client->hset($serverName, $orchestratorNumber, $serializedOrchestrator);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setOrchestrator(OrchestratorInterface $runtimeOrchestrator): CacheHandlerInterface
    {
        $this->client->hset(
            $this->serverName,
            $this->orchestratorNumber,
            $this->serializeOrchestrator($runtimeOrchestrator)
        );

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getOrchestrator(string $serverName = null, string $orchestratorNumber = null): OrchestratorInterface
    {
        if (is_null($orchestratorNumber)) {
            $orchestratorNumber = $this->orchestratorNumber;
        }

        if (is_null($serverName)) {
            $serverName = $this->serverName;
        }

        if (is_null($this->client->hget($serverName, $orchestratorNumber))) {
            throw RedisException::forCacheOrchestratorNumberNotFound($orchestratorNumber);
        }

        $runtimeOrchestrator = $this->unserializeOrchestrator($this->client->hget($serverName, $orchestratorNumber));

        return $runtimeOrchestrator;
    }

    /**
     * {@inheritDoc}
     */
    public function getOrchestratorsByServerName(string $serverName = null, string $className): ?array
    {
        if (is_null($serverName) && is_null($this->serverName)) {
            throw RedisException::forServerNameNotFound();
        }

        // Get all serverName hashmap keys.
        $keys = $this->client->hkeys($serverName);

        if (empty($keys)) {
            return null;
        }

        // Using the regular to filter the className.
        $pattern = '/^' . preg_quote($className, '/') . '/';

        $filteredData = preg_grep($pattern, $keys);

        $orchestratorData = [];

        // Put the filtered key and it's value in the array.
        foreach ($filteredData as $key => $orchestratorNumber) {
            $orchestratorData[$orchestratorNumber] = $this->unserializeOrchestrator(
                $this->client->hget($serverName, $orchestratorNumber)
            );
        }

        return $orchestratorData;
    }

    /**
     * {@inheritDoc}
     */
    public function getOrchestratorsByClassName(string $className): ?array
    {
        // Get all of serverName in Redis.
        $serverNameList = $this->client->smembers("serverNameList");

        if (empty($serverNameList)) {
            return null;
        }

        $serverOrchestratorData = [];

        foreach ($serverNameList as $key => $serverName) {
            $serverOrchestratorData[$serverName] = $this->getOrchestratorsByServerName($serverName, $className);
        }

        return $serverOrchestratorData;
    }

    /**
     * {@inheritDoc}
     */
    public function clearOrchestrator(string $serverName = null, string $orchestratorNumber = null): bool
    {
        if (is_null($orchestratorNumber)) {
            $orchestratorNumber = $this->orchestratorNumber;
        }

        if (is_null($serverName)) {
            $serverName = $this->serverName;
        }

        if (is_null($this->client->hget($serverName, $orchestratorNumber))) {
            throw RedisException::forCacheOrchestratorNumberNotFound($orchestratorNumber);
        }

        // Delete the orch number in hashmap.
        $result = ($this->client->hdel($serverName, $orchestratorNumber)) == 1;

        // If the $serverName hashmap is empty after delete orch, remove the $serverName from serverNameList.
        if ($result === true && empty($this->client->hgetall($serverName))) {
            $this->client->srem("serverNameList", $serverName);
        }

        return $result;
    }
}
