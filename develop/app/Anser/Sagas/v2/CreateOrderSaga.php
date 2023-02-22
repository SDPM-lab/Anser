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

        $orderKey = $this->getOrchestrator()->orderKey;

        $orderService->deleteOrder($orderKey)->do();
    }

    /**
     * The Compensation function for product inventory reducing.
     *
     * @return void
     */
    public function productInventoryReduceCompensation()
    {
        $productService = new ProductService();

        $productKey = $this->getOrchestrator()->productKey;
        $addAmount  = $this->getOrchestrator()->addAmount;

        // It still need the error condition.
        // It will compensate the product inventory balance Only if the error code is 5XX error.

        $productService->addInventory($productKey, $addAmount)->do();
    }

    /**
     * The Compensation function for user wallet balance reducing.
     *
     * @return void
     */
    public function walletBalanceReduceCompensation()
    {
        $paymentService = new PaymentService();

        $userKey = $this->getOrchestrator()->userKey;

        $increaseBalance = $this->getOrchestrator()->increaseBalance;

        // It still need the error condition.
        // It will compensate the wallet balance Only if the error code is 5XX error.

        $paymentService->increaseWalletBalance($userKey, $increaseBalance)->do();
    }

    /**
     * The Compensation function for payment creating.
     *
     * @return void
     */
    public function paymentCreateCompensation()
    {
        $paymentService = new PaymentService();

        $paymentKey = $this->getOrchestrator()->paymentKey;

        $paymentService->deletePayment($paymentKey)->do();
    }
}
