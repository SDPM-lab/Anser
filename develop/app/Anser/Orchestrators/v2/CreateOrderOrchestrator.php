<?php

namespace App\Anser\Orchestrators\V2;

use App\Anser\Sagas\V2\CreateOrderSaga;
use SDPMlab\Anser\Orchestration\Orchestrator;
use App\Anser\Services\V2\ProductService;
use App\Anser\Services\V2\PaymentService;
use App\Anser\Services\V2\OrderService;
use Exception;
use SDPMlab\Anser\Orchestration\OrchestratorInterface;
use SDPMlab\Anser\Orchestration\Saga\Cache\CacheFactory;

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
     * The user key of this orchestrator.
     *
     * @var string
     */
    public $user_key = null;

    /**
     * The product information.
     *
     * @var array
     */
    public array $product_data = [];

    /**
     * The order key.
     *
     * @var integer
     */
    public $order_key;

    /**
     * The product key.
     *
     * @var integer
     */
    public $product_key;

    /**
     * The product price * amount.
     *
     * @var int
     */
    public $total = 0;

    /**
     * The product amount.
     *
     * @var integer
     */
    public $product_amount = 0;

    /**
     * The payment key.
     *
     * @var string|null
     */
    public $payment_key = null;

    public function __construct()
    {
        $this->productService = new ProductService();
        $this->paymentService = new PaymentService();
        $this->orderService   = new OrderService();
    }

    protected function definition(int $product_key = null, int $product_amount = null, int $user_key = null)
    {
        if (is_null($product_key) || is_null($user_key) || is_null($product_amount)) {
            throw new Exception("The parameters of product or user_key fail.");
        }

        $this->user_key       = $user_key;
        $this->product_amount = $product_amount;
        $this->product_key    = $product_key;

        $cache = CacheFactory::initCacheDriver('redis', 'tcp://anser_redis:6379');

        $this->setServerName("Anser_Server_1");

        // Step 1. Check the product inventory balance.
        $step1 = $this->setStep()->addAction(
            "product_check",
            $this->productService->checkProductInventory($product_key, $product_amount)
        );

        // Step 2. Get product info.
        $step2 = $this->setStep()->addAction(
            "get_product_info",
            $this->productService->getProduct($product_key)
        );

        // Define the closure of step3.
        $step3Closure = static function (
            OrchestratorInterface $runtimeOrch
        ) use (
            $user_key,
            $product_amount
        ) {
            $product_data = $runtimeOrch->getStepAction("get_product_info")->getMeaningData();
            $total        = $product_data["price"] * $product_amount;

            $runtimeOrch->product_data = &$product_data;
            $runtimeOrch->total        = $total;

            $action = $runtimeOrch->paymentService->checkWalletBalance($user_key, $runtimeOrch->total);
            return $action;
        };

        // Step 3. Check the user wallet balance.
        $step3 = $this->setStep()->addAction("wallet_check", $step3Closure);

        $this->transStart(CreateOrderSaga::class);

        // Define the closure of step4.
        $step4Closure = static function (
            OrchestratorInterface $runtimeOrch
        ) use (
            $user_key,
            $product_amount,
            $product_key
        ) {
            return $runtimeOrch->orderService->createOrder(
                $user_key,
                $product_key,
                $product_amount,
                $runtimeOrch->product_data["price"],
                $runtimeOrch->getOrchestratorNumber()
            );
        };

        // Step 4. Create order.
        $step4 = $this->setStep()
            ->setCompensationMethod("orderCreateCompensation")
            ->addAction(
                "create_order",
                $step4Closure
            );

        // Define the closure of step5.
        $step5Closure = static function (
            OrchestratorInterface $runtimeOrch
        ) use (
            $user_key,
            $product_amount
        ) {
            $order_key = $runtimeOrch->getStepAction("create_order")->getMeaningData();

            $runtimeOrch->order_key = $order_key;

            $action = $runtimeOrch->paymentService->createPayment(
                $user_key,
                $runtimeOrch->order_key,
                $product_amount,
                $runtimeOrch->total,
                $runtimeOrch->getOrchestratorNumber()
            );

            return $action;
        };

        // Step 5. Create payment.
        $step5 = $this->setStep()
            ->setCompensationMethod("paymentCreateCompensation")
            ->addAction(
                "create_payment",
                $step5Closure
            );

        $step6Closure = static function (
            OrchestratorInterface $runtimeOrch
        ) use ($product_key, $product_amount) {
            $payment_key = $runtimeOrch->getStepAction("create_payment")->getMeaningData();

            $runtimeOrch->payment_key = $payment_key;

            return $runtimeOrch->productService->reduceInventory(
                $product_key,
                $product_amount,
                $runtimeOrch->getOrchestratorNumber()
            );
        };

        // Step 6. Reduce the product inventory amount.
        $step6 = $this->setStep()
            ->setCompensationMethod("productInventoryReduceCompensation")
            ->addAction(
                "reduce_product_amount",
                $step6Closure
            );

        // Define the closure of step7.
        $step7Closure = static function (
            OrchestratorInterface $runtimeOrch
        ) use ($user_key) {
            return $runtimeOrch->paymentService->reduceWalletBalance(
                $user_key,
                $runtimeOrch->total,
                $runtimeOrch->getOrchestratorNumber()
            );
        };

        // Step 7. Reduce the user wallet balance.
        $step7 = $this->setStep()
            ->setCompensationMethod("walletBalanceReduceCompensation")
            ->addAction(
                "reduce_wallet_balance",
                $step7Closure
            );

        $this->transEnd();
    }

    protected function defineResult()
    {
        $data["data"] = [
            "status"    => $this->isSuccess(),
            "order_key" => $this->order_key,
            "product_data" => $this->product_data,
            "total"        => $this->total
        ];

        if (!$this->isSuccess()) {
            $data["data"]["isCompensationSuccess"] = $this->isCompensationSuccess();
        }

        return $data;
    }
}
