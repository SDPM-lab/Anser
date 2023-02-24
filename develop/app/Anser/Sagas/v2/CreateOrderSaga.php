<?php

namespace App\Anser\Sagas\V2;

use SDPMlab\Anser\Orchestration\Saga\SimpleSaga;
use App\Anser\Services\V2\OrderService;
use App\Anser\Services\V2\ProductService;
use App\Anser\Services\V2\PaymentService;

class CreateOrderSaga extends SimpleSaga
{
    /**
     * The Compensation function for order creating.
     *
     * @return void
     */
    public function orderCreateCompensation()
    {
        $orderService = new OrderService();

        $orchestratorNumber = $this->getOrchestrator()->getOrchestratorNumber();
        $user_key  = $this->getOrchestrator()->user_key;

        $orderService->deleteOrderByRuntimeOrch($user_key, $orchestratorNumber)->do();
    }

    /**
     * The Compensation function for product inventory reducing.
     *
     * @return void
     */
    public function productInventoryReduceCompensation()
    {
        $productService = new ProductService();

        $orchestratorNumber = $this->getOrchestrator()->getOrchestratorNumber();
        $product_amount     = $this->getOrchestrator()->product_amount;

        // It still need the error condition.
        // It will compensate the product inventory balance Only if the error code is 5XX error.

        $productService->addInventoryByRuntimeOrch($product_amount, $orchestratorNumber)->do();
    }

    /**
     * The Compensation function for user wallet balance reducing.
     *
     * @return void
     */
    public function walletBalanceReduceCompensation()
    {
        $paymentService = new PaymentService();

        $orchestratorNumber = $this->getOrchestrator()->getOrchestratorNumber();
        $user_key = $this->getOrchestrator()->user_key;
        $total    = $this->getOrchestrator()->total;

        // It still need the error condition.
        // It will compensate the wallet balance Only if the error code is 5XX error.

        $paymentService->increaseWalletBalance($user_key, $total, $orchestratorNumber)->do();
    }

    /**
     * The Compensation function for payment creating.
     *
     * @return void
     */
    public function paymentCreateCompensation()
    {
        $paymentService = new PaymentService();

        $orchestratorNumber = $this->getOrchestrator()->getOrchestratorNumber();
        $payment_key = $this->getOrchestrator()->payment_key;
        $user_key    = $this->getOrchestrator()->user_key;

        $paymentService->deletePaymentByRuntimeOrch($user_key, $orchestratorNumber)->do();
    }
}
