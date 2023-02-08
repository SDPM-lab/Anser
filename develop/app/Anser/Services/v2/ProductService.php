<?php

namespace App\Anser\Services\V2;

use SDPMlab\Anser\Service\SimpleService;
use SDPMlab\Anser\Exception\ActionException;
use Psr\Http\Message\ResponseInterface;
use SDPMlab\Anser\Service\Action;


class ProductService extends SimpleService
{
    protected $serviceName = "product_service";

    protected $retry      = 1;
    protected $retryDelay = 1;
    protected $timeout    = 5.0;

    public function getAllProduct()
    {
        $action = $this->getAction("GET", "/api/v2/product")
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
