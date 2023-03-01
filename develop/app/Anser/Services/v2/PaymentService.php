<?php

namespace App\Anser\Services\V2;

use SDPMlab\Anser\Service\SimpleService;
use SDPMlab\Anser\Service\ActionInterface;
use Psr\Http\Message\ResponseInterface;
use SDPMlab\Anser\Exception\ActionException;
use SDPMlab\Anser\Service\Action;

class PaymentService extends SimpleService
{
    protected $serviceName = "payment_service";

    protected $retry      = 1;
    protected $retryDelay = 1;
    protected $timeout    = 10.0;

    /**
     * Get all payment
     *
     * @param integer $u_key
     * @param integer|null $limit
     * @param integer|null $offset
     * @param string|null $search
     * @param string|null $isDesc
     * @return ActionInterface
     */
    public function getAllPayment(
        int $u_key,
        ?int $limit = null,
        ?int $offset = null,
        ?string $search = null,
        ?string $isDesc = null
    ): ActionInterface {
        $action = $this->getAction("GET", "/api/v2/payment");

        $payload = [];

        if (!is_null($limit)) {
            $payload["limit"]  = $limit;
        }
        if (!is_null($offset)) {
            $payload["offset"] = $offset;
        }
        if (!is_null($search)) {
            $payload["search"] = $search;
        }
        if (!is_null($isDesc)) {
            $payload["isDesc"] = $isDesc;
        }

        if (!empty($payload)) {
            $action->addOption("query", $payload);
        }

        $action->addOption("headers", [
            "X-User-Key" => $u_key
        ]);

        $action->doneHandler(
            function (
                ResponseInterface $response,
                Action $action
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
                    $e->getAction()->setMeaningData(["message" => $e->getMessage()]);
                }
            );
        return $action;
    }

    /**
     * Get payment by payment_key
     *
     * @param integer $u_key
     * @param string $order_key
     * @return ActionInterface
     */
    public function getPayment(int $payment_key): ActionInterface
    {
        $action = $this->getAction("GET", "/api/v2/payment/{$payment_key}")
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
            ->failHandler(
                function (
                    ActionException $e
                ) {
                    log_message("critical", $e->getMessage());
                    $e->getAction()->setMeaningData(["message" => $e->getMessage()]);
                }
            );
        return $action;
    }

    /**
     * Create payment
     *
     * @param integer $u_key
     * @param string $o_key
     * @param integer $amount
     * @param integer $price
     * @param string $orch_key
     * @return ActionInterface
     */
    public function createPayment(int $u_key, string $o_key, int $amount, int $price, string $orch_key): ActionInterface
    {
        $action = $this->getAction("POST", "/api/v2/payment")
            ->addOption("json", [
                "o_key"  => $o_key,
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
                    Action $action
                ) {
                    $resBody = $response->getBody()->getContents();
                    $data    = json_decode($resBody, true);
                    $action->setMeaningData($data["paymentID"]);
                }
            )
            ->failHandler(
                function (
                    ActionException $e
                ) {
                    log_message("critical", $e->getMessage());
                    $e->getAction()->setMeaningData(["message" => $e->getMessage()]);
                }
            );
        return $action;
    }

    /**
     * Update payment
     *
     * @param integer $payment_key
     * @param integer|null $total
     * @param string|null $status
     * @return ActionInterface
     */
    public function updatePayment(
        int $payment_key = 0,
        ?int $total = null,
        ?string $status = null,
        int $u_key = 0,
        string $orch_key = ""
    ): ActionInterface {
        $action = $this->getAction("PUT", "/api/v2/payment/{$payment_key}")
            ->addOption("json", [
                "total"  => $total,
                "status" => $status,
            ])
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
                    $action->setMeaningData($data["status"]);
                }
            )
            ->failHandler(
                function (
                    ActionException $e
                ) {
                    log_message("critical", $e->getMessage());
                    $e->getAction()->setMeaningData(["message" => $e->getMessage()]);
                }
            );
        return $action;
    }

    /**
     * Delete payment
     *
     * @param string $payment_key
     * @param string $u_key
     * @param string $orch_key
     * @return ActionInterface
     */
    public function deletePayment(string $payment_key, string $u_key, string $orch_key): ActionInterface
    {
        $action = $this->getAction("DELETE", "/api/v2/payment/{$payment_key}")
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
                    $action->setMeaningData($data["status"]);
                }
            )
            ->failHandler(
                function (
                    ActionException $e
                ) {
                    log_message("critical", $e->getMessage());
                    $e->getAction()->setMeaningData(["message" => $e->getMessage()]);
                }
            );
        return $action;
    }

    /**
     * Delete payment data by runtime orch number.
     *
     * @param string $u_key
     * @param string $orch_key
     * @return ActionInterface
     */
    public function deletePaymentByRuntimeOrch(string $u_key, string $orch_key): ActionInterface
    {
        $action = $this->getAction("DELETE", "/api/v2/payment")
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
                    $action->setMeaningData($data["status"]);
                }
            )
            ->failHandler(
                function (
                    ActionException $e
                ) {
                    log_message("critical", $e->getMessage());
                    $e->getAction()->setMeaningData(["message" => $e->getMessage()]);
                }
            );
        return $action;
    }

    /**
     * show user wallet
     *
     * @param integer $u_key
     * @return ActionInterface
     */
    public function getWallet(int $u_key): ActionInterface
    {
        $action = $this->getAction("GET", "/api/v2/wallet")
            ->addOption("headers", [
                "X-User-Key" => $u_key
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
            ->failHandler(
                function (
                    ActionException $e
                ) {
                    log_message("critical", $e->getMessage());
                    $e->getAction()->setMeaningData(["message" => $e->getMessage()]);
                }
            );
        return $action;
    }

    /**
     * Check user wallet balance logic.
     *
     * @param integer $u_key
     * @param integer $cost
     * @return ActionInterface
     */
    public function checkWalletBalance(int $u_key, int $cost): ActionInterface
    {
        $action = $this->getAction("GET", "/api/v2/wallet")
        ->addOption("headers", [
            "X-User-Key" => $u_key
        ])
            ->doneHandler(
                function (
                    ResponseInterface $response,
                    Action $action
                ) use ($cost) {
                    $resBody = $response->getBody()->getContents();
                    $data    = json_decode($resBody, true);

                    $action->setSuccess($data["data"]["balance"] >= $cost);
                }
            )
            ->failHandler(
                function (
                    ActionException $e
                ) {
                    log_message("critical", $e->getMessage());
                    $e->getAction()->setMeaningData(["message" => $e->getMessage()]);
                }
            );
        return $action;
    }

    /**
     * increase user wallet balance
     *
     * @param integer $u_key
     * @param integer $increaseBalance
     * @param string $orch_key
     * @return ActionInterface
     */
    public function increaseWalletBalance(int $u_key, int $increaseBalance, string $orch_key): ActionInterface
    {
        $action = $this->getAction("POST", "/api/v2/wallet/increaseWalletBalance")
            ->addOption("json", [
                "addAmount" => $increaseBalance
            ])
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
                    $action->setMeaningData($data["status"]);
                }
            )
            ->failHandler(
                function (
                    ActionException $e
                ) {
                    log_message("critical", $e->getMessage());
                    $e->getAction()->setMeaningData(["message" => $e->getMessage()]);
                }
            );
        return $action;
    }

    /**
     * reduce user wallet balance
     *
     * @param integer $u_key
     * @param integer $reduceBalance
     * @param string $orch_key
     * @return ActionInterface
     */
    public function reduceWalletBalance(int $u_key, int $reduceBalance, string $orch_key): ActionInterface
    {
        $action = $this->getAction("POST", "/api/v2/wallet/reduceWalletBalance")
            ->addOption("json", [
                "reduceAmount" => $reduceBalance
            ])
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
                    $action->setMeaningData($data["status"]);
                }
            )
            ->failHandler(
                function (
                    ActionException $e
                ) {
                    log_message("critical", $e->getMessage());
                    $e->getAction()->setMeaningData(["message" => $e->getMessage()]);
                }
            );
        return $action;
    }

    /**
     * Get payment history by orch_key.
     *
     * @param string $orch_key
     * @return ActionInterface
     */
    public function getPaymentHistory(string $orch_key): ActionInterface
    {
        $action = $this->getAction('POST', "/api/v2/history/getPaymentHistory")
            ->addOption("json", [
                "orch_key"  => $orch_key,
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
            ->failHandler(
                function (
                    ActionException $e
                ) {
                    log_message("critical", $e->getMessage());
                    $e->getAction()->setMeaningData(["message" => $e->getMessage()]);
                }
            );
        return $action;
    }

    /**
     * Get wallet history by orch_key.
     *
     * @param string $orch_key
     * @return ActionInterface
     */
    public function getWalletHistory(string $orch_key): ActionInterface
    {
        $action = $this->getAction('POST', "/api/v2/history/getWalletHistory")
            ->addOption("json", [
                "orch_key"  => $orch_key,
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
            ->failHandler(
                function (
                    ActionException $e
                ) {
                    log_message("critical", $e->getMessage());
                    $e->getAction()->setMeaningData(["message" => $e->getMessage()]);
                }
            );
        return $action;
    }
}
