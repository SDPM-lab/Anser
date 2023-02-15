<?php

namespace App\Anser\Sagas;

use SDPMlab\Anser\Orchestration\Saga\SimpleSaga;
use App\Anser\Services\V2\OrderService;
use App\Anser\Services\V2\ProductService;
use App\Anser\Services\V2\PaymentService;

class CreateOrderSaga extends SimpleSaga
{
    /**
     * Order Compensation
     *
     * @return void
     */
    public function orderCompensation()
    {
        $orderService = new OrderService();

        $orderKey = $this->getOrchestrator()->orderKey;

        $orderService->deleteOrder($orderKey)->do();
    }

    /**
     * product inventory compensation
     *
     * @return void
     */
    public function productInventoryCompensation()
    {
        $productService = new ProductService();

        $productKey = $this->getOrchestrator()->productKey;
        $addAmount  = $this->getOrchestrator()->addAmount;

        $productService->addInventory($productKey, $addAmount)->do();
    }

    /**
     * user wallet balance compensation
     *
     * @return void
     */
    public function walletBalanceCompensation()
    {
        $paymentService = new PaymentService();

        $userKey = $this->getOrchestrator()->userKey;

        $increaseBalance = $this->getOrchestrator()->increaseBalance;

        $paymentService->increaseWalletBalance($userKey, $increaseBalance)->do();
    }

    /**
     * payment compensation
     *
     * @return void
     */
    public function paymentCompensation()
    {
        $paymentService = new PaymentService();

        $paymentKey = $this->getOrchestrator()->paymentKey;

        $paymentService->deletePayment($paymentKey)->do();
    }
}
