<?php

namespace App\Anser\Services\V2;

use SDPMlab\Anser\Service\SimpleService;
use SDPMlab\Anser\Service\ActionInterface;
use SDPMlab\Anser\Exception\ActionException;

class OrderService extends SimpleService
{
    protected $serviceName = "order_Service";

    protected $retry = 1;
    protected $retryDelay = 1;
    protected $timeout = 3.0;
}
