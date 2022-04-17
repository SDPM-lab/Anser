<?php

namespace SDPMlab\Anser\Orchestration\Saga;

use SDPMlab\Anser\Orchestration\StepInterface;

interface StateInterface
{
    public function setStepProceeesing(StepInterface $step);
    public function setStepCompensating(StepInterface $step);
    public function update(int $state);
    public function getNowStep(): StepInterface;
    public function getNowState(): int;

}