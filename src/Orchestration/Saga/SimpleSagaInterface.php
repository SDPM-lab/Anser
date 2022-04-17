<?php

namespace SDPMlab\Anser\Orchestration\Saga;

use SDPMlab\Anser\Orchestration\OrchestratorInterface;

interface SimpleSagaInterface
{
    /**
     * 取得執行時期的編排器實體
     *
     * @return OrchestratorInterface
     */
    public function getOrchestrator(): OrchestratorInterface;
}
