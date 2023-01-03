<?php

namespace App\Anser\Orchestrators;

use SDPMlab\Anser\Orchestration\Orchestrator;
use App\Anser\Services\UserService;

class UserOrchestrator extends Orchestrator
{

    /**
     * The sample of service.
     *
     * @var UserService
     */
    protected UserService $userService;

    public function __construct()
    {
        $this->userService = new UserService();   
    }

    protected function definition()
    {
        
    }
}
