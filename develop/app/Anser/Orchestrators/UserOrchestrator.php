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
        $orderAction   = new Action("order_service", "GET", "/api/v1/order/1");
        $paymentAction = new Action("payment_service", "GET", "/api/v1/payment/1");
        $userAction    = new Action("user_service", "GET", "/api/v1/user");

        $this->setCacheInstance(CacheFactory::initCacheDriver('redis', 'tcp://service_redis:6379'));
        $this->setCacheOrchestratorKey("userOrder");
        
        $this->setStep()
            ->addAction("order", $orderAction);
        $this->setStep()
            ->addAction("payment", $paymentAction);
        $this->setStep()
            ->addAction("user", $userAction);
    }
}
