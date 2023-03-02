<?php

namespace SDPMlab\Anser\Orchestration\Saga;

use SDPMlab\Anser\Orchestration\OrchestratorInterface;
use SDPMlab\Anser\Orchestration\StepInterface;

interface SagaInterface
{
    /**
     * Start the orchestrator step.
     *
     * @param StepInterface $step
     * @return void
     */
    public function startStep(StepInterface $step);

    /**
     * Set the distributed transcation start step.
     *
     * @param StepInterface $step
     * @return void
     */
    public function setStartStep(StepInterface $step);

    /**
     * Set the distributed transcation end step.
     *
     * @param StepInterface $step
     * @return void
     */
    public function setEndStep(StepInterface $step);
    
    /**
     * Get status.
     *
     * @return StateInterface
     */
    public function getState(): StateInterface;

    /**
     * Start the whole orchestrator compensation.
     *
     * @param array $stepList
     * @return boolean|null
     */
    public function startCompensation(array $stepList): ?bool;

    /**
     * Start the step of orchestrator compensation.
     *
     * @param StepInterface $step
     * @return void
     */
    public function startStepCompensation(StepInterface $step);

    /**
     * Bind the compenstation method and orchestrator step.
     *
     * @param string $methodName
     * @param integer $stepNumber
     * @return void
     */
    public function bindCompensationMethod(string $methodName, int $stepNumber);

    /**
     * Set the runtime orchestrator to state and simpleSaga class after de-serialize.
     *
     * @param OrchestratorInterface $runtimeOrch
     * @return SagaInterface
     */
    public function setRuntimeOrchestrator(OrchestratorInterface $runtimeOrch): SagaInterface;
}
