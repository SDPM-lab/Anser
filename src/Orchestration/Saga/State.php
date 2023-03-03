<?php

namespace SDPMlab\Anser\Orchestration\Saga;

use SDPMlab\Anser\Orchestration\OrchestratorInterface;
use SDPMlab\Anser\Orchestration\Saga\StateInterface;
use SDPMlab\Anser\Orchestration\StepInterface;

class State implements StateInterface
{
    public const start = 0;
    public const stepProcessing = 1;
    public const stepCompensating = 2;
    public const compensated = 3;
    public const end = 4;

    /**
     * The now on status.
     *
     * @var integer
     */
    protected int $nowState;

    /**
     * Store the now on step class.
     *
     * @var StepInterface|null
     */
    protected ?StepInterface $nowStep = null;

    /**
     * The runtime orchestrator.
     *
     * @var OrchestratorInterface
     */
    protected OrchestratorInterface $runtimeOrch;

    public function __construct(
        OrchestratorInterface $runtimeOrch
    ) {
        $this->runtimeOrch = $runtimeOrch;
    }

    public function __sleep()
    {
        return [
            "nowState",
            "nowStep"
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function setRuntimeOrchestrator(OrchestratorInterface $runtimeOrch): StateInterface
    {
        $this->runtimeOrch = &$runtimeOrch;
        return $this;
    }
    
    /**
     * {@inheritDoc}
     */
    public function setStepProceeesing(StepInterface $step)
    {
        if (is_null($this->nowStep)) {
            $this->nowState = State::stepProcessing;
        }
        $this->nowStep = $step;
    }

    /**
     * {@inheritDoc}
     */
    public function setStepCompensating(StepInterface $step)
    {
        $this->nowState = State::stepCompensating;
        $this->nowStep = $step;
    }

    /**
     * {@inheritDoc}
     */
    public function update(int $state)
    {
        $this->nowState = $state;
    }

    /**
     * {@inheritDoc}
     */
    public function getNowStep(): ?StepInterface
    {
        return $this->nowStep;
    }

    /**
     * {@inheritDoc}
     */
    public function getNowState(): int
    {
        return $this->nowState;
    }
}
