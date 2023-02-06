<?php

namespace App\Anser\Orchestrators;

use SDPMlab\Anser\Orchestration\Orchestrator;
use SDPMlab\Anser\Service\Action;
use App\Anser\Services\UserService;
use SDPMlab\Anser\Orchestration\Saga\Cache\CacheFactory;

class UserOrchestrator extends Orchestrator
{

    /**
     * The sample of service.
     *
     * @var UserService
     */
    protected UserService $userService;

    protected $cache;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    protected function definition()
    {
        $this->cache = CacheFactory::initCacheDriver('redis', 'tcp://service_redis:6379');

        $this->setCacheInstance($this->cache);
        $this->setCacheOrchestratorKey(random_int(0,10000000000000));

        $userService   = new UserService();

        $this->setStep()->addAction("user_service", $userService->getUserData("1"));
    }

    protected function defineResult()
    {
        // $this->cache->clearOrchestrator("userOrder_15");

        return $this->isSuccess;
    }
}
