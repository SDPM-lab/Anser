<?php

namespace App\Anser\Orchestrators\V2;

use SDPMlab\Anser\Orchestration\Orchestrator;
use App\Anser\Services\V2\ProductService;
use Exception;
use SDPMlab\Anser\Orchestration\Saga\Cache\CacheFactory;
use SDPMlab\Anser\Service\Action;

class CreateOrderOrchestrator extends Orchestrator
{

    /**
     * The sample of service.
     *
     * @var UserService
     */
    protected ProductService $productService;

    /**
     * Store the product info.
     *
     * @var array
     */
    protected array $productArray = [];

    /**
     * Store the product Actions.
     *
     * @var array
     */
    protected array $productActions = [];

    protected $orderKey;

    /**
     * Cache instance.
     *
     */
    protected $cache;

    public function __construct()
    {
        $this->productService = new ProductService();
    }

    protected function definition(array $productsArray = [], int $userKey = null)
    {
        if (empty($productsArray) || is_null($userKey)) {
            throw new Exception("The parameters of product or userKey fail.");
        }

        // Step 1. Check the product inventory balance.
        $step1 = $this->setStep();
        foreach ($productsArray as $key => $product_key) {
            $actionName = "product_{$product_key}";

            $this->productActions[] = $actionName;

            $step1->addAction($actionName, $this->productService->getProduct($product_key));
        }
        
        // Step 2. Check the user wallet balance.
        // $step2 = $this->setStep()->addAction("wallet_check", new Action("payment_service", "GET"));
        // Step 3. Create order.

        // Step 4. Reduce the product inventory amount.

        // Step 5. Reduce the user wallet balance.

    }

    protected function defineResult()
    {
        if ($this->isSuccess()) {
            // foreach ($this->getStepAction("product_1")) {
            // }
            // return $this->getStepAction();

            foreach ($this->productActions as $key => $actionName) {
                $proudcts[] = $this->getStepAction($actionName)->getMeaningData();
            }

            return json_encode($proudcts);
        }
    }
}
