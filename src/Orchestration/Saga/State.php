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

    protected int $nowState;
    protected ?StepInterface $nowStep = null;
    protected OrchestratorInterface $runtimeOrch;

    public function __construct(
        OrchestratorInterface $runtimeOrch
    ) {
        $this->runtimeOrch = $runtimeOrch;
    }

    public function setStepProceeesing(StepInterface $step)
    {
        if (is_null($this->nowStep)) {
            $this->nowState = State::stepProcessing;
        }
        $this->nowStep = $step;
    }

    public function setStepCompensating(StepInterface $step)
    {
        $this->nowState = State::stepCompensating;
        $this->nowStep = $step;
    }

    public function update(int $state)
    {
        $this->nowState = $state;
    }

    public function getNowStep(): StepInterface
    {
        return $this->nowStep;
    }

    public function getNowState(): int
    {
        return $this->nowState;
    }
}
