<?php

namespace App\Controllers\V1;

use App\Controllers\BaseController;
use SDPMlab\Anser\Orchestration\Saga\Restarter\Restarter;

class UserOrderRestarter extends BaseController
{
    public function restartUserOrchestrator()
    {
        $userOrchRestarter = new Restarter('userOrder_1');
        $userOrchRestarter->reStartOrchestrator();
    }
}
