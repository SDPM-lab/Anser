<?php

namespace App\Controllers\V1;

use App\Controllers\BaseController;
use SDPMlab\Anser\Orchestration\Saga\Cache\CacheFactory;

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
        $this->client = CacheFactory::initCacheDriver('redis', [
            'scheme' => 'tcp',
            'host'   => 'service_redis',
            'port'   => '6379'
        ], null);
    }

    public function index()
    {
        var_dump($this->client->getOrchestratorStatus('2'));
    }
}
