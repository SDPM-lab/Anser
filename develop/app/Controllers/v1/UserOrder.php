<?php

namespace App\Controllers\V1;

use App\Controllers\BaseController;
use App\Anser\Orchestrators\UserOrchestrator;
use SDPMlab\Anser\Service\ServiceList;

class UserOrder extends BaseController
{
    public function __construct()
    {
        // ServiceList::addLocalService("order_service", "localhost", 8080, false);
        // ServiceList::addLocalService("payment_service", "localhost", 8080, false);
        // ServiceList::addLocalService("fail_service", "localhost", 8080, false);
        ServiceList::addLocalService("userService", "localhost", 8080, false);
    
    }

    public function userOrder()
    {
        $userOrch = new UserOrchestrator();
        $res = $userOrch->build();
        // var_dump($userOrch);
    }
}
