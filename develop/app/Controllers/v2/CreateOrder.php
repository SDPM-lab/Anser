<?php

namespace App\Controllers\V2;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Anser\Orchestrators\V2\CreateOrderOrchestrator;
use SDPMlab\Anser\Service\ServiceList;
use App\Services\UserService;

class CreateOrder extends BaseController
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
