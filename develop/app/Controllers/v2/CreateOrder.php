<?php

namespace App\Controllers\V2;

use App\Controllers\BaseController;
use App\Anser\Orchestrators\V2\CreateOrderOrchestrator;
use SDPMlab\Anser\Service\ServiceList;

class CreateOrder extends BaseController
{
    
    public function createOrder()
    {
        $userOrch = new CreateOrderOrchestrator();

        $result   = $userOrch->build();
        
        var_dump($result);
    }
}
