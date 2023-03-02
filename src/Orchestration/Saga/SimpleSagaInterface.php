<?php

namespace SDPMlab\Anser\Orchestration\Saga;

use SDPMlab\Anser\Orchestration\OrchestratorInterface;

interface SimpleSagaInterface
{
    /**
     * Get the runtime orchestrator.
     *
     * @return OrchestratorInterface
     */
    public function getOrchestrator(): OrchestratorInterface;

    /**
     * Set the runtime orchestrator after de-serialize.
     *
     * @param OrchestratorInterface $runtimeOrch
     * @return SimpleSagaInterface
     */
    public function setRuntimeOrchestrator(OrchestratorInterface $runtimeOrch): SimpleSagaInterface;
}
