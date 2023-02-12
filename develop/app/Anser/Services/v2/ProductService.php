<?php

namespace App\Anser\Services\V2;

use SDPMlab\Anser\Service\SimpleService;
use SDPMlab\Anser\Exception\ActionException;
use Psr\Http\Message\ResponseInterface;
use SDPMlab\Anser\Service\Action;
use SDPMlab\Anser\Service\ActionInterface;

class ProductService extends SimpleService
{
    protected $serviceName = "product_service";

    protected $retry      = 1;
    protected $retryDelay = 1;
    protected $timeout    = 5.0;

    /**
     * Get all product
     *
     * @param integer|null $limit
     * @param integer|null $offset
     * @param string|null $isDesc
     * @return ActionInterface
     */
    public function getAllProduct(?int $limit, ?int $offset, ?string $isDesc): ActionInterface
    {
        $action = $this->getAction("GET", "/api/v2/product");

        $payload = [];

        if (!is_null($limit)) {
            $payload["limit"]  = $limit;
        }
        if (!is_null($offset)) {
            $payload["offset"] = $offset;
        }
        if (!is_null($isDesc)) {
            $payload["isDesc"] = $isDesc;
        }

        if (!empty($payload)) {
            $action->addOption("query", $payload);
        }

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
     * Get a product by product key
     *
     * @param integer $product_key
     * @return ActionInterface
     */
    public function getProduct(int $product_key): ActionInterface
    {
        $action = $this->getAction("GET", "/api/v2/product/{$product_key}")
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
     * Create product
     *
     * @param string $name
     * @param integer $price
     * @param integer $amount
     * @return ActionInterface
     */
    public function createProduct(string $name, int $price, int $amount): ActionInterface
    {
        $action = $this->getAction("POST", "/api/v2/product")
            ->addOption("json", [
                "name"        => $name,
                "price"       => $price,
                "amount"      => $amount
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
     * Update the product
     *
     * @param integer $p_key
     * @param string $name
     * @param integer $price
     * @return ActionInterface
     */
    public function updateProduct(int $p_key, string $name, int $price): ActionInterface
    {
        $action = $this->getAction("PUT", "/api/v2/product/{$p_key}")
            ->addOption("json", [
                "name"        => $name,
                "price"       => $price,
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
     * Delete the product
     *
     * @param integer $p_key
     * @return ActionInterface
     */
    public function deleteProduct(int $p_key): ActionInterface
    {
        $action = $this->getAction("DELETE", "/api/v2/product/{$p_key}")
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
