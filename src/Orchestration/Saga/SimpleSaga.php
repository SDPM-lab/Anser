<?php

namespace SDPMlab\Anser\Orchestration\Saga;

use SDPMlab\Anser\Orchestration\Saga\SimpleSagaInterface;
use SDPMlab\Anser\Orchestration\OrchestratorInterface;

class SimpleSaga implements SimpleSagaInterface
{
    private OrchestratorInterface $orchestrator;

    public function __construct(OrchestratorInterface $orchestrator)
    {
        $this->orchestrator = $orchestrator;
    }

    /**
     * 取得執行時期的編排器實體
     *
     * @return OrchestratorInterface
     */
    public function getOrchestrator(): OrchestratorInterface
    {
        return $this->orchestrator;
    }
}
