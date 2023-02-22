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

        $order_key = $this->getOrchestrator()->order_key;

        $orderService->deleteOrder($order_key)->do();
    }

    /**
     * The Compensation function for product inventory reducing.
     *
     * @return void
     */
    public function productInventoryReduceCompensation()
    {
        $productService = new ProductService();

        $product_key    = $this->getOrchestrator()->product_key;
        $product_amout  = $this->getOrchestrator()->product_amout;

        // It still need the error condition.
        // It will compensate the product inventory balance Only if the error code is 5XX error.

        $productService->addInventory($product_key, $product_amout)->do();
    }

    /**
     * The Compensation function for user wallet balance reducing.
     *
     * @return void
     */
    public function walletBalanceReduceCompensation()
    {
        $paymentService = new PaymentService();

        $user_key = $this->getOrchestrator()->user_key;

        $product_amout = $this->getOrchestrator()->product_amout;

        // It still need the error condition.
        // It will compensate the wallet balance Only if the error code is 5XX error.

        $paymentService->increaseWalletBalance($user_key, $product_amout)->do();
    }

    /**
     * The Compensation function for payment creating.
     *
     * @return void
     */
    public function paymentCreateCompensation()
    {
        $paymentService = new PaymentService();

        $payment_key = $this->getOrchestrator()->payment_key;

        $paymentService->deletePayment($payment_key)->do();
    }
}
