<?php

namespace SDPMlab\Anser\Orchestration\Saga;

use SDPMlab\Anser\Orchestration\Saga\SimpleSagaInterface;
use SDPMlab\Anser\Orchestration\OrchestratorInterface;

class SimpleSaga implements SimpleSagaInterface
{
    /**
     * The runtime orchestrator.
     *
     * @var OrchestratorInterface
     */
    private OrchestratorInterface $orchestrator;

    public function __construct(OrchestratorInterface $orchestrator)
    {
        $this->orchestrator = &$orchestrator;
    }

    public function __sleep()
    {
        return [];   
    }

    /**
     * {@inheritDoc}
     */
    public function setRuntimeOrchestrator(OrchestratorInterface $runtimeOrch): SimpleSagaInterface
    {
        $this->orchestrator = &$runtimeOrch;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getOrchestrator(): OrchestratorInterface
    {
        return $this->orchestrator;
    }
}
