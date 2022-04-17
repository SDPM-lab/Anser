<?php

namespace SDPMlab\Anser\Orchestration\Saga;

use SDPMlab\Anser\Orchestration\StepInterface;

interface SagaInterface
{
    public function startStep(StepInterface $step);
    public function setStartStep(StepInterface $step);
    public function setEndStep(StepInterface $step);
    public function getState(): StateInterface;
    public function startCompensation(array $stepList): ?bool;
    public function startStepCompensation(StepInterface $step);
    public function bindCompensationMethod(string $methodName, int $stepNumber);
}