<?php

namespace App\Anser\Orchestrators\V1;

use SDPMlab\Anser\Orchestration\Orchestrator;
use SDPMlab\Anser\Service\Action;
use App\Anser\Services\V1\UserService;
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
        $this->cache = CacheFactory::initCacheDriver('redis', 'tcp://anser_redis:6379');

        $this->setCacheOrchestratorKey('userOrder_2');

        $userService   = new UserService();

        $this->setStep()->addAction("user_service", $userService->getUserData("1"));
    }

    protected function defineResult()
    {
        // $this->cache->clearOrchestrator("userOrder_15");

        return $this->isSuccess;
    }
}
