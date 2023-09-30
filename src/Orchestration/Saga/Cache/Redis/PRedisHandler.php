<?php

namespace SDPMlab\Anser\Orchestration\Saga\Cache\Redis;

use SDPMlab\Anser\Orchestration\Saga\Cache\BaseCacheHandler;
use SDPMlab\Anser\Orchestration\Saga\Cache\CacheHandlerInterface;
use SDPMlab\Anser\Orchestration\Saga\Cache\Redis\Config;
use SDPMlab\Anser\Orchestration\OrchestratorInterface;
use SDPMlab\Anser\Exception\RedisException;
use Predis\Client;
use Predis\ClientException;

class PRedisHandler extends BaseCacheHandler
{
    protected Config $config;

    protected $option = [];

    /**
     * The client of Redis.
     *
     * @var client
     */
    protected $client;

    public function __construct(Config $config = null, ?array $option = null)
    {
        parent::__construct();
        $this->config = $config;
        $this->option = $option;
        
        try {
            $this->client = new Client([
                'scheme' => $this->config->scheme,
                'host'   => $this->config->host,
                'port'   => $this->config->port,
                'timeout' => (int) $this->config->timeout
            ], $this->option);
            $this->client->select($this->config->db);
        } catch (ClientException $e) {
            throw new ClientException($e->getMessage());
        }
    }

    public function __destruct()
    {
        $this->client->disconnect();
    }

    public function __sleep()
    {
        $this->client->disconnect();
        return ['config', 'option', 'path'];
    }

    public function __wakeup()
    {
        $this->client = new Client([
            'scheme' => $this->config->scheme,
            'host'   => $this->config->host,
            'port'   => $this->config->port,
            'timeout' => (int) $this->config->timeout
        ], $this->option);
        $this->client->select($this->config->db);
    }

    /**
     * {@inheritDoc}
     */
    public function initOrchestrator(OrchestratorInterface $runtimeOrchestrator): CacheHandlerInterface
    {
        $orchestratorNumber = $runtimeOrchestrator->getOrchestratorNumber();
        $serverName         = $this->config->serverName;

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
            $this->config->serverName,
            $runtimeOrchestrator->getOrchestratorNumber(),
            $this->serializeOrchestrator($runtimeOrchestrator)
        );

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getOrchestrator(string $orchestratorNumber = null): OrchestratorInterface
    {
        if (is_null($this->client->hget($this->config->serverName, $orchestratorNumber))) {
            throw RedisException::forCacheOrchestratorNumberNotFound($orchestratorNumber);
        }

        $runtimeOrchestrator = $this->unserializeOrchestrator(
            $this->client->hget($this->config->serverName, $orchestratorNumber)
        );

        return $runtimeOrchestrator;
    }

    /**
     * {@inheritDoc}
     */
    public function getOrchestrators(string $className, ?string $serverName = null): ?array
    {
        if($serverName === null){
            $serverName = $this->config->serverName;
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
    public function getServersOrchestrator(string $className): ?array
    {
        // Get all of serverName in Redis.
        $serverNameList = $this->client->smembers("serverNameList");

        if (empty($serverNameList)) {
            return null;
        }

        $serverOrchestratorData = [];

        foreach ($serverNameList as $key => $serverName) {
            $serverOrchestratorData[$serverName] = $this->getOrchestrators($className, $serverName);
        }

        return $serverOrchestratorData;
    }

    /**
     * {@inheritDoc}
     */
    public function clearOrchestrator(OrchestratorInterface $runtimeOrchestrator): bool
    {
        $orchestratorNumber = $runtimeOrchestrator->getOrchestratorNumber();
        if (is_null($this->client->hget($this->config->serverName, $orchestratorNumber))) {
            throw RedisException::forCacheOrchestratorNumberNotFound($orchestratorNumber);
        }

        // Delete the orch number in hashmap.
        $result = ($this->client->hdel($this->config->serverName, $orchestratorNumber)) == 1;

        // If the $serverName hashmap is empty after delete orch, remove the $serverName from serverNameList.
        if ($result === true && empty($this->client->hgetall($this->config->serverName))) {
            $this->client->srem("serverNameList", $this->config->serverName);
        }

        return $result;
    }

}
