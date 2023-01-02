<?php

namespace App\Controllers\V1;

use App\Controllers\BaseController;
use SDPMlab\Anser\Orchestration\Saga\Cache\CacheFactory;
use App\Anser\Orchestrators\UserOrchestrator;

class Redis extends BaseController
{
    /**
     * The redis of orchestrator controller instance.
     *
     * @var CacheHandlerInterface
     */
    protected $client;

    public function __construct()
    {
        $this->client = CacheFactory::initCacheDriver('redis', 'tcp://service_redis:6379');
    }

    public function index()
    {
        var_dump($this->client->getOrchestratorStatus('2'));
    }

    public function testCacheSerialize()
    {
        $orch = new UserOrchestrator();

        $orchNumber = 16;

        $this->client->initOrchestrator($orchNumber, $orch);
        $this->client->setOrchestrator($orch);
        var_dump($this->client->getOrchestrator() == $orch);
    }
}
