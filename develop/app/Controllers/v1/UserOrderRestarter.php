<?php

namespace App\Controllers\V1;

use App\Controllers\BaseController;
use SDPMlab\Anser\Orchestration\Saga\Restarter\Restarter;
use SDPMlab\Anser\Orchestration\Saga\Cache\CacheFactory;

class UserOrderRestarter extends BaseController
{
    public function restartUserOrchestrator()
    {
        $userOrchRestarter = new Restarter('userOrder_8');
        $userOrchRestarter->setRestarterCacheInstance(CacheFactory::initCacheDriver('redis', 'tcp://anser_redis:6379'))
                          ->reStartOrchestrator();
    }
}