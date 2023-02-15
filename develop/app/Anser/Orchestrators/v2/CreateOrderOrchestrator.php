<?php

namespace App\Anser\Orchestrators\V2;

use SDPMlab\Anser\Orchestration\Orchestrator;
use App\Anser\Services\V2\ProductService;
use App\Anser\Services\V2\PaymentService;
use App\Anser\Services\V2\OrderService;
use Exception;
use SDPMlab\Anser\Orchestration\OrchestratorInterface;
use SDPMlab\Anser\Orchestration\Saga\Cache\CacheFactory;
use SDPMlab\Anser\Service\Action;

class CreateOrderOrchestrator extends Orchestrator
{

    /**
     * The service of product.
     *
     * @var ProductService
     */
    protected ProductService $productService;

    /**
     * The service of payment.
     *
     * @var PaymentService
     */
    protected PaymentService $paymentService;

    /**
     * The service of order.
     *
     * @var OrderService
     */
    protected OrderService $orderService;

    /**
     * The product information.
     *
     * @var array
     */
    protected array $product_data;

    /**
     * The order key.
     *
     * @var integer
     */
    protected $order_key;

    /**
     * Cache instance.
     *
     */
    protected $cache;

    public function __construct()
    {
        $this->productService = new ProductService();
        $this->paymentService = new PaymentService();
        $this->orderService   = new OrderService();
    }

    protected function definition(int $product_key = null, int $product_amout = null, int $user_key = null)
    {
        if (empty($productsArray) || is_null($user_key)) {
            throw new Exception("The parameters of product or user_key fail.");
        }

        // Step 1. Check the product inventory balance.
        $step1 = $this->setStep()->addAction("product_check", $this->productService->checkProductInventory($product_key, $product_amout));

        // Step 2. Get product info.
        $step2 = $this->setStep()->addAction("get_product_info", $this->productService->getProduct($product_key));
       
        // Step 3. Check the user wallet balance.
        $step3 = $this->setStep()->addAction("wallet_check", function (OrchestratorInterface $runtimeOrch) use ($user_key) {
            $data = $runtimeOrch->getStepAction("get_product_info")->getMeaningData();
            $this->product_data = $data["data"];
            $this->paymentService->checkWalletBalance($user_key, $this->product_data["price"]);
        });

        // Step 4. Create order.
        $step4 = $this->setStep()->addAction("create_order", $this->orderService->createOrder($user_key, $product_key, $product_amout, $this->product_data["price"] * $product_amout));

        // Step 5. Reduce the product inventory amount.
        $step5 = $this->setStep()->addAction("reduce_product_amount", $this->productService->reduceInventory($product_key, $product_amout));

        // Step 6. Reduce the user wallet balance.
        $step6 = $this->setStep()->addAction("reduce_wallet_balance", $this->paymentService->reduceWalletBalance($user_key, $this->product_data["price"] * $product_amout));
    }

    protected function defineResult()
    {
        if ($this->isSuccess()) {
            $order_data = $this->getStepAction("create_order")->getMeaningData();
            $this->order_key = $order_data["data"]["orderID"];
            
            return json_encode([
                "data" => [
                    "status"       => true,
                    "order_key"    => $this->order_key
                ]
            ]);
        }
    }
}
