<?php

namespace App\Controllers\V2;

use App\Anser\Orchestrators\V2\CreateOrderOrchestrator;
use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use SDPMlab\Anser\Orchestration\Saga\Restarter\Restarter;
use SDPMlab\Anser\Orchestration\Saga\Cache\CacheFactory;

class CreateOrderRestarter extends BaseController
{
    use ResponseTrait;

    public function restartCreateOrderOrchestrator()
    {
        CacheFactory::initCacheDriver('redis', 'tcp://anser_redis:6379');
        $userOrchRestarter = new Restarter();
        $userOrchRestarter->reStartOrchestrator(CreateOrderOrchestrator::class);
    }
}
