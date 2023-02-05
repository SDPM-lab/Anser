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

    public function __construct()
    {
        $this->userService = new UserService();
    }

    protected function definition()
    {
        $this->setCacheInstance(CacheFactory::initCacheDriver('redis', 'tcp://service_redis:6379'));
        $this->setCacheOrchestratorKey("userOrder_1");
        
        // $this->setStep()
        //     ->addAction("order", $orderAction);
        // $this->setStep()
        //     ->addAction("payment", $paymentAction);
        // $this->setStep()
        //     ->addAction("user", $userAction);

        // After fixed.

        $userService   = new UserService();

        $this->setStep()->addAction("user_service", $userService->getUserData("1"));
    }
}
