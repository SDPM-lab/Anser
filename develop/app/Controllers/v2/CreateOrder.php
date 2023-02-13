<?php

namespace App\Controllers\V2;

use App\Controllers\BaseController;
use App\Anser\Orchestrators\V2\CreateOrderOrchestrator;
use SDPMlab\Anser\Service\ServiceList;
use App\Services\UserService;

class CreateOrder extends BaseController
{
    
    public function createOrder()
    {
        $data = $this->request->getJSON(true);

        $productsArray = $data["products"];
        $userKey       = $this->request->getHeaderLine("X-User-Key");
        
        $userOrch = new CreateOrderOrchestrator();

        $result   = $userOrch->build($productsArray, $userKey);
        
        var_dump(json_decode($result));
    }
}
