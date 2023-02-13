<?php

namespace App\Anser\Services\V2;

use SDPMlab\Anser\Service\SimpleService;
use SDPMlab\Anser\Service\ActionInterface;
use Psr\Http\Message\ResponseInterface;
use SDPMlab\Anser\Exception\ActionException;
use SDPMlab\Anser\Service\Action;

class PaymentService extends SimpleService
{
    protected $serviceName = "payment_Service";

    protected $retry = 1;
    protected $retryDelay = 1;
    protected $timeout = 3.0;

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
     * @param integer $o_key
     * @param integer $amount
     * @param integer $price
     * @return ActionInterface
     */
    public function createPayment(int $u_key, int $o_key, int $amount, int $price): ActionInterface
    {
        $action = $this->getAction("POST", "/api/v2/payment")
            ->addOption("json", [
                "o_key"  => $o_key,
                "price"  => $price,
                "amount" => $amount
            ])
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
        int $payment_key,
        ?int $total = null,
        ?string $status = null
    ): ActionInterface {
        $action = $this->getAction("PUT", "/api/v2/payment/{$payment_key}")
            ->addOption("json", [
                "total"  => $total,
                "status" => $status,
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
     * @return ActionInterface
     */
    public function deletePayment(string $payment_key): ActionInterface
    {
        $action = $this->getAction("DELETE", "/api/v2/payment/{$payment_key}")
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
}
