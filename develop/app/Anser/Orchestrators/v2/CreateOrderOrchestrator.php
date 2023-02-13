<?php

namespace App\Anser\Orchestrators\V2;

use SDPMlab\Anser\Orchestration\Orchestrator;
use App\Anser\Services\V2\ProductService;
use SDPMlab\Anser\Orchestration\Saga\Cache\CacheFactory;

class CreateOrderOrchestrator extends Orchestrator
{

    /**
     * The sample of service.
     *
     * @var UserService
     */
    protected ProductService $productService;

    protected $cache;

    public function __construct()
    {
        $this->productService = new ProductService();
    }

    protected function definition()
    {
        // $this->cache = CacheFactory::initCacheDriver('redis', 'tcp://anser_redis:6379');

        // $this->setCacheInstance($this->cache);
        // $this->setCacheOrchestratorKey('createOrder_1');
        $this->setStep()->addAction("product_service", $this->productService->getAllProduct());
    }

    protected function defineResult()
    {
        return $this->isSuccess;
    }
}
