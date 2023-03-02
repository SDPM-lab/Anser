<?php

namespace SDPMlab\Anser\Orchestration\Saga;

use SDPMlab\Anser\Orchestration\OrchestratorInterface;
use SDPMlab\Anser\Orchestration\StepInterface;

interface StateInterface
{
    /**
     * Set status of the pass in step is processing.
     *
     * @param StepInterface $step
     * @return void
     */
    public function setStepProceeesing(StepInterface $step);

    /**
     * Set status of the pass in step is compensating.
     *
     * @param StepInterface $step
     * @return void
     */
    public function setStepCompensating(StepInterface $step);

    /**
     * Update the status.
     *
     * @param integer $state
     * @return void
     */
    public function update(int $state);

    /**
     * Get now step and return.
     *
     * @return StepInterface|null
     */
    public function getNowStep(): ?StepInterface;

    /**
     * Get now status.
     *
     * @return integer
     */
    public function getNowState(): int;

    /**
     * Set the runtime orchestrator after de-serialize.
     *
     * @param OrchestratorInterface $runtimeOrch
     * @return StateInterface
     */
    public function setRuntimeOrchestrator(OrchestratorInterface $runtimeOrch): StateInterface;
}
