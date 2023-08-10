# Anser：PHP 微服務協作程式庫

<p align="center">
  <img src="https://i.imgur.com/2vRAcI0.png" alt="logo" width="500" />
</p>

Anser 是一款基於 PHP 程式語言的微服務協作（Microservices Orchestration）程式庫，你可以透過這個程式庫管理、連線與指揮你的微服務。透過 Anser 程式庫，你可以輕鬆地達成以下目標：

- 替每個基於 HTTP 的微服務抽象出特定類別與實作，Anser 並不會限制你溝通模式。
- 快速的組合（Composition） 你的微服務
- 撰寫具備順序性的微服務指揮腳本
- 迅速地採用 SAGA 模式制定你的交易（Transaction）邏輯
- 簡單的交易備份機制，服務意外中斷時的交易還原

## 安裝

透過 Composer 安裝 Anser 程式庫：

```bash
composer require sdpmlab/anser
```

## 快速開始

### 微服務連線列表

在你的專案中，你必須在執行週期內設定微服務的連線列表，你可以透過 `ServiceList::addLocalService()` 方法設定，你參考我們提供的範例建立你的微服務連線列表，這將會是所有微服務連線的基礎。：

```php
namespace App\Anser\Config;

use SDPMlab\Anser\Service\ServiceList;

ServiceList::addLocalService("order_service","localhost",8080,false);
ServiceList::addLocalService("product_service","localhost",8081,false);
ServiceList::addLocalService("cart_service","localhost",8082,false);
ServiceList::addLocalService("payment_service","localhost",8083,false);
```

### 抽象出微服務

在 Anser 中，你可以透過 `SimpleService` 類別抽象出一個微服務的所有端點，我們提供了一個範例，你可以參考它快速地建立一個微服務類別：

```php
namespace App\Anser\Services;

use SDPMlab\Anser\Service\SimpleService;
use SDPMlab\Anser\Service\ActionInterface;
use SDPMlab\Anser\Exception\ActionException;
use Psr\Http\Message\ResponseInterface;

class OrderService extends SimpleService
{
    protected $serviceName = "order_service";
    protected $retry      = 1;
    protected $retryDelay = 1;
    protected $timeout    = 10.0;

    /**
     * Get order by order_key
     *
     * @param integer $u_key
     * @param string $order_key
     * @return ActionInterface
     */
    public function getOrder(
        int $u_key,
        string $order_key
    ): ActionInterface {
        $action = $this->getAction("GET", "/api/v2/order/{$order_key}")
            ->addOption("headers", [
                    "X-User-Key" => $u_key
                ])
            ->doneHandler(
                function (
                    ResponseInterface $response,
                    ActionInterface $action
                ) {
                    $resBody = $response->getBody()->getContents();
                    $data    = json_decode($resBody, true);
                    $action->setMeaningData($data["data"]);
                }
            )
            ->failHandler(
                function (
                    ActionException $e
                ) {
                    log_message("critical", $e->getMessage());
                    $e->getAction()->setMeaningData([
                        "message" => $e->getMessage()
                    ]);
                }
            );
        return $action;
    }

    /**
     * Create order
     *
     * @param integer $u_key
     * @param integer $p_key
     * @param integer $amount
     * @param integer $price
     * @param string $orch_key
     * @return ActionInterface
     */
    public function createOrder(
        int $u_key,
        int $p_key,
        int $amount,
        int $price,
        string $orch_key
    ): ActionInterface {
        $action = $this->getAction("POST", "/api/v2/order")
            ->addOption("json", [
                "p_key"  => $p_key,
                "price"  => $price,
                "amount" => $amount
            ])
            ->addOption("headers", [
                "X-User-Key" => $u_key,
                "Orch-Key"   => $orch_key
            ])
            ->doneHandler(
                function (
                    ResponseInterface $response,
                    ActionInterface $action
                ) {
                    $resBody = $response->getBody()->getContents();
                    $data    = json_decode($resBody, true);
                    $action->setMeaningData($data["orderID"]);
                }
            )
            ->failHandler(
                function (
                    ActionException $e
                ) {
                    log_message("critical", $e->getMessage());
                    $e->getAction()->setMeaningData([
                        "message" => $e->getMessage()
                    ]);
                }
            );
        return $action;
    }

    /**
     * Delete order
     *
     * @param string $order_key
     * @param string $u_key
     * @param string $orch_key
     * @return ActionInterface
     */
    public function deleteOrderByOrchKey(
        string $u_key,
        string $orch_key
    ): ActionInterface {
        $action = $this->getAction('DELETE', "/api/v2/order")
            ->addOption("headers", [
                "X-User-Key" => $u_key,
                "Orch-Key"   => $orch_key
            ])
            ->doneHandler(
                function (
                    ResponseInterface $response,
                    Action $action
                ) {
                    $resBody = $response->getBody()->getContents();
                    $data    = json_decode($resBody, true);
                    $action->setMeaningData($data["data"]);
                }
            )
            ->failHandler($this->getFailHandler());
        return $action;
    }

    /**
     * Fail handler
     * 
     * @return callable
     */
    protected function getFailHandler(): callable {
        return function (
            ActionException $e
        ) {
            log_message("critical", $e->getMessage());
            $e->getAction()->setMeaningData([
                "message" => $e->getMessage()
            ]);
        };
    }

}
```

你可以直接參考 [`Anser-Action`](https://github.com/SDPM-lab/Anser-Action) 程式庫，了解 Anser 提供了何種機制處理微服務的連線。

### 編排你的微服務

在 Anser 中，你可以透過 `Orchestrator` 類別編排你的微服務，我們提供了一個範例，你可以參考它快速地建立一個編排類別：

```php
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

        // Step 3. Check the user wallet balance.
        $step3 = $this->setStep()
        ->addAction(
            "wallet_check",
            // Define the closure of step3.
            static function (
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
            }
        );

        // Start the saga.
        $this->transStart(CreateOrderSaga::class);

        // Step 4. Create order.
        $step4 = $this->setStep()
        ->setCompensationMethod("orderCreateCompensation")
        // Define the closure of step4.
        ->addAction(
            "create_order",
            static function (
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
            }
        );

        // Step 5. Create payment.
        $step5 = $this->setStep()
        ->setCompensationMethod("paymentCreateCompensation")
        ->addAction(
            "create_payment",
            // Define the closure of step5.
            static function (
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
            }
        );

        // Step 6. Reduce the product inventory amount.
        $step6 = $this->setStep()
        ->setCompensationMethod("productInventoryReduceCompensation")
        ->addAction(
            "reduce_product_amount",
            // Define the closure of Step 6.
            static function (
                OrchestratorInterface $runtimeOrch
            ) use ($product_key, $product_amount) {
                $payment_key = $runtimeOrch->getStepAction("create_payment")->getMeaningData();

                $runtimeOrch->payment_key = $payment_key;

                return $runtimeOrch->productService->reduceInventory(
                    $product_key,
                    $product_amount,
                    $runtimeOrch->getOrchestratorNumber()
                );
            }
        );

        // Step 7. Reduce the user wallet balance.
        $step7 = $this->setStep()
        ->setCompensationMethod("walletBalanceReduceCompensation")
        ->addAction(
            "reduce_wallet_balance",
            // Define the closure of step 7.
            static function (
                OrchestratorInterface $runtimeOrch
            ) use ($user_key) {
                return $runtimeOrch->paymentService->reduceWalletBalance(
                    $user_key,
                    $runtimeOrch->total,
                    $runtimeOrch->getOrchestratorNumber()
                );
            }
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

```

在上述的範例中，我們可以看到在 `definition` 方法中，我們透過 `setStep()` 方法來定義每個步驟的行為，並且透過 `addAction()` 方法來定義每個步驟所需執行的邏輯。

在 `addAction()` 中你可以傳入兩種型別以達成不同的編排需求：

1. 傳入 `SDPMlab\Anser\Service\ActionInterface` 的實體，當微服務協作器執行到這個步驟時，將直接使用這個 `Action` 實體與微服務進行溝通。
2. 傳入 `callable` ，當微服務協作器執行到這個步驟時，將會執行這個 Closure 並傳入 Runtime 的 Orchestrator ，你可以透過 Runtime 的 Orchestrator 實體取得其他步驟的資料以滿足更多的邏輯判斷需求，並在結束時回傳 `SDPMlab\Anser\Service\ActionInterface` 的實體。

使用 `transStart()` 方法來啟動一個 Saga 交易，並且在 `transEnd()` 方法中來結束這個 Saga 交易。接著，你將可以透過 `setCompensationMethod()` 方法來定義每個步驟的補償行為，當步驟發生錯誤時，會自動執行補償行為。

### 定義補償行為

在上述的範例中，我們可以看到在 `definition` 方法中，我們透過 `setCompensationMethod()` 方法來定義每個步驟的補償行為，當步驟發生錯誤時，會自動執行補償行為。

你必須額外實作 `SDPMlab\Anser\Orchestration\Saga\SimpleSaga` 類別來定義你的補償邏輯，並且在補償邏輯中透過 `getOrchestrator()` 方法來取得 Runtime 的 Orchestrator 實體，你可以透過 Runtime 的 Orchestrator 實體取得其他步驟的資料以滿足更多的邏輯判斷需求。

```php
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
```

### 執行你所編排的微服務邏輯

根據你所使用的框架的不同，你會需要將你所撰寫的 Orchestrator 在某個地方執行。

下方是一個概略的範例：

```php
use App\Anser\Orchestrators\V2\CreateOrderOrchestrator;

class CreateOrderController extends BaseController
{
    use ResponseTrait;

    public function createOrder()
    {
        $data = $this->request->getJSON(true);

        $product_key    = $data["product_key"];
        $product_amout  = $data["product_amout"];
        $user_key       = $this->request->getHeaderLine("X-User-Key");

        $userOrch = new CreateOrderOrchestrator();

        $result   = $userOrch->build($product_key, $product_amout, $user_key);

        return $this->respond($result);
    }
}
```

我們可以看到在 `createOrder()` 方法中，我們 `new CreateOrderOrchestrator();` 了一個 Orchestrator 實體，並透過 `build()` 方法來啟動一個包含 Saga 交易的服務協作，並且在 `build()` 方法中傳入了 `product_key`、`product_amout`、`user_key` 三個參數，這些參數將會在 `definition()` 方法中被使用。

最後，你將在 `build()` 執行完成後獲得回傳值，這個回傳值源自於 `defineResult()` 所處理後的資料。

以上就是一個全功能的 Anser Orchestrator Saga 的使用範例，你可以透過這個範例來了解 Anser Orchestrator 的使用方式。

## 授權條款

Anser 是基於 [MIT license](https://opensource.org/licenses/MIT) 釋出。