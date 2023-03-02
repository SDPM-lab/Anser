<?php

namespace SDPMlab\Anser\Orchestration\Saga;

use SDPMlab\Anser\Exception\SagaException;
use SDPMlab\Anser\Orchestration\StepInterface;
use SDPMlab\Anser\Orchestration\Saga\SagaInterface;
use SDPMlab\Anser\Orchestration\Saga\StateInterface;
use SDPMlab\Anser\Orchestration\OrchestratorInterface;
use SDPMlab\Anser\Orchestration\Saga\SimpleSagaInterface;

class Saga implements SagaInterface
{
    /**
     * 開發者所實作之 SAGA 實體
     *
     * @var SimpleSagaInterface
     */
    protected SimpleSagaInterface $simpleSagaInstance;

    /**
     * SAGA 狀態物件
     *
     * @var StateInterface
     */
    protected StateInterface $stateInstance;

    /**
     * 依照 step 順序存入之 compensationMethod
     *
     * @var array
     */
    protected array $compensationMethods;

    /**
     * 需要被補償的 Step 清單
     *
     * @var array<StepInterface>
     */
    protected array $compensationStepList = [];

    /**
     * 交易會在哪個 Step 完成後結束
     *
     * @var integer|null
     */
    protected ?int $endStep = null;

    /**
     * 交易從哪個 Step 開始
     *
     * @var integer|null
     */
    protected ?int $startStep = null;

    public function __construct(
        string $simpleSagaClassName,
        OrchestratorInterface $runtimeOrchestrator,
        int $startStepNumber
    ) {
        if (class_exists($simpleSagaClassName)) {
            $this->simpleSagaInstance = new $simpleSagaClassName($runtimeOrchestrator);
        } else {
            throw SagaException::forSimpleSagaNotFound($simpleSagaClassName);
        }

        $this->stateInstance = new State($runtimeOrchestrator);
        $this->stateInstance->update(State::start);
        $this->startStep = $startStepNumber;
    }

    /**
     * {@inheritDoc}
     */
    public function setRuntimeOrchestrator(OrchestratorInterface $runtimeOrch): SagaInterface
    {
        $this->stateInstance->setRuntimeOrchestrator($runtimeOrch);
        $this->simpleSagaInstance->setRuntimeOrchestrator($runtimeOrch);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function startStep(StepInterface $step)
    {
        if (
            $step->getNumber() >= $this->startStep &&
            $step->getNumber() <= $this->endStep
        ) {
            $this->stateInstance->setStepProceeesing($step);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function setStartStep(StepInterface $step)
    {
        $this->startStep = $step->getNumber();
    }

    /**
     * {@inheritDoc}
     */
    public function setEndStep(StepInterface $step)
    {
        $this->endStep = $step->getNumber();
    }

    /**
     * {@inheritDoc}
     */
    public function getState(): StateInterface
    {
        return $this->stateInstance;
    }

    /**
     * 判斷是否需要執行補償
     *
     * @return boolean
     */
    protected function canStartCompensation(): bool
    {
        $nowStep = $this->stateInstance->getNowStep()?->getNumber();

        if (is_null($nowStep)) {
            return false;
        }

        if (
            $this->startStep <= $nowStep &&
            $this->endStep >= $nowStep
        ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function startCompensation(array $stepList): ?bool
    {
        if ($this->canStartCompensation() === false) {
            return null;
        }

        $nowStepNumber = $this->stateInstance->getNowStep()->getNumber();

        // Remove not run steps yet from step list.
        $this->compensationStepList = array_chunk($stepList, $nowStepNumber + 1)[0];

        // Remove the first few step that not in compensation step in compensationStepList.
        $this->compensationStepList = array_slice($this->compensationStepList, $this->startStep);

        while (count($this->compensationStepList) > 0) {
            $this->startStepCompensation(array_pop($this->compensationStepList));
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function startStepCompensation(StepInterface $step)
    {
        $this->stateInstance->setStepCompensating($step);
        $stepNumber = $step->getNumber();

        if (isset($this->compensationMethods[$stepNumber])) {
            $method = $this->compensationMethods[$stepNumber];
            return $this->simpleSagaInstance->{$method}();
        } else {
            throw SagaException::forCompensationMethodNotFoundForStep($stepNumber);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function bindCompensationMethod(string $methodName, int $stepNumber)
    {
        if (method_exists($this->simpleSagaInstance, $methodName)) {
            $this->compensationMethods[$stepNumber] = $methodName;
        } else {
            throw SagaException::forCompensationMethodNotFound($methodName);
        }
    }
}
