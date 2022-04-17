<?php

namespace SDPMlab\Anser\Orchestration\Saga;

use SDPMlab\Anser\Orchestration\OrchestratorInterface;
use SDPMlab\Anser\Orchestration\Saga\StateInterface;
use SDPMlab\Anser\Orchestration\StepInterface;

class State implements StateInterface
{

    const start = 0;
    const stepProcessing = 1;
    const stepCompensating = 2;
    const compensated = 3;
    const end = 4;

    protected int $nowState;
    protected ?StepInterface $nowStep = null;
    protected OrchestratorInterface $runtimeOrch;

    public function __construct(
        OrchestratorInterface $runtimeOrch
    )
    {
        $this->runtimeOrch = $runtimeOrch;
    }

    public function setStepProceeesing(StepInterface $step)
    {
        if(is_null($this->nowStep)) $this->nowState = State::stepProcessing;
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
