<?php

namespace App\Controllers\V2;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use SDPMlab\Anser\Orchestration\Saga\Restarter\Restarter;
use SDPMlab\Anser\Orchestration\Saga\Cache\CacheFactory;

class CreateOrderRestarter extends BaseController
{
    use ResponseTrait;

    public function restartcreateOrderOrchestrator()
    {
        $userOrchRestarter = new Restarter('createOrder_2');
        $userOrchRestarter->setRestarterCacheInstance(CacheFactory::initCacheDriver('redis', 'tcp://anser_redis:6379'))
                            ->reStartOrchestrator();
    }
}
