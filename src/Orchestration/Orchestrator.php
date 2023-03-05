<?php

namespace SDPMlab\Anser\Orchestration;

use SDPMlab\Anser\Orchestration\Step;
use SDPMlab\Anser\Orchestration\StepInterface;
use SDPMlab\Anser\Exception\OrchestratorException;
use SDPMlab\Anser\Orchestration\Saga\SagaInterface;
use SDPMlab\Anser\Orchestration\Saga\Saga;
use SDPMlab\Anser\Orchestration\Saga\Cache\CacheFactory;
use SDPMlab\Anser\Orchestration\Saga\Cache\CacheHandlerInterface;
use SDPMlab\Anser\Service\ActionInterface;

abstract class Orchestrator implements OrchestratorInterface
{
    /**
     * 儲存被新增的 step 實體
     *
     * @var array<StepInterface>
     */
    protected $steps = [];

    /**
     * 判斷是否執行成功
     *
     * @var bool
     */
    protected $isSuccess = true;

    /**
     * Check whether the saga compensation run successfully.
     *
     * @var boolean
     */
    protected $isCompensationSuccess = true;

    /**
     * SAGA 實體
     *
     * @var SagaInterface|null
     */
    protected ?SagaInterface $sagaInstance = null;

    /**
     * The parameter of build funcion.
     *
     * @var array|null
     */
    protected ?array $argsArray = null;

    /**
     * The number of this orchestrator.
     * (Using in Cache scanerio.)
     *
     * @var string|null
     */
    protected ?string $orchestratorNumber = null;

    /**
     * The serverName of this orchestrator.
     * (Using in Cache scanerio.)
     *
     * @var string|null
     */
    protected ?string $serverName = null;

    /**
     * Set the runtime orch to the class need to store after de-serialize.
     */
    public function __wakeup()
    {
        foreach ($this->steps as $step) {
            $step->setRuntimeOrchestrator($this);
        }

        if (!is_null($this->sagaInstance)) {
            $this->sagaInstance->setRuntimeOrchestrator($this);
        }
    }

    /**
     * 設定一個新的 Step
     *
     * @return StepInterface
     */
    public function setStep(): StepInterface
    {
        $step = new Step($this, count($this->steps));
        $this->steps[] = $step;
        return $step;
    }

    /**
     * {@inheritDoc}
     */
    public function getStep(int $index): StepInterface
    {
        if (is_null($this->steps[$index])) {
            throw OrchestratorException::forStepNotFoundInSteps($index);
        }

        return $this->steps[$index];
    }

    /**
     * {@inheritDoc}
     */
    public function setServerName(string $serverName): OrchestratorInterface
    {
        $this->serverName = $serverName;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getOrchestratorNumber(): ?string
    {
        return $this->orchestratorNumber;
    }

    /**
     * 取得 Saga 實體
     *
     * @return SagaInterface|null
     */
    public function getSagaInstance(): ?SagaInterface
    {
        return $this->sagaInstance;
    }

    /**
     * {@inheritDoc}
     */
    public function transStart(string $transactionClass): OrchestratorInterface
    {
        $startStepNumber = count($this->steps) > 0 ? count($this->steps) : 0;
        $this->sagaInstance = new Saga(
            $transactionClass,
            $this,
            $startStepNumber
        );
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function transEnd(): OrchestratorInterface
    {
        $this->sagaInstance->setEndStep(end($this->steps));
        return $this;
    }

    /**
     * 判斷在目前被設定的 Step 之中，傳入的別名從來沒有被使用過。
     *
     * @param string $alias
     * @return boolean
     * @throws OrchestratorException
     */
    public function checkAliasInAllSteps(string $alias): bool
    {
        foreach ($this->steps as $step) {
            if (!$step->aliasNonRepeat($alias)) {
                throw OrchestratorException::forAliasRepeat($alias);
            }
        }
        return true;
    }

    /**
     * 取得目前 Orchestrator Steps 中符合傳入別名的 Action 實體
     *
     * @param string $alias
     * @return ActionInterface
     * @throws OrchestratorException
     */
    public function getStepAction(string $alias): ActionInterface
    {
        foreach ($this->steps as $step) {
            if (!$step->aliasNonRepeat($alias)) {
                return $step->getStepAction($alias);
            }
        }
        throw OrchestratorException::forActionNotFound($alias);
    }

    /**
     * 取得在所有 Step 中失敗的 Action 列表，若全數成功則回傳空陣列。
     * 其資料結構為 陣列<別名,Action實體>
     *
     * @return array<string,\SDPMlab\Anser\Service\ActionInterface>
     */
    public function getFailActions(): array
    {
        $actions = [];
        foreach ($this->steps as $step) {
            foreach ($step->getStepActionList() as $alias => $action) {
                if (!$action->isSuccess()) {
                    $actions[$alias] = $action;
                }
            }
        }
        return $actions;
    }

    /**
     * 執行 Orchestrator
     *
     * @param mixed ...$args
     * @return mixed
     */
    final public function build(...$args)
    {
        $this->argsArray = func_get_args();

        call_user_func_array(array($this, "definition"), $this->argsArray);

        $this->startAllStep();

        $result = $this->defineResult();

        return $result;
    }

    /**
     * Handle the single step of steps array.
     *
     * @param StepInterface $step
     * @return void
     */
    protected function handleSingleStep(StepInterface $step)
    {
        $cacheInstance = CacheFactory::getCacheInstance();

        // 將當前 Step 紀錄於 Saga
        if (!is_null($this->sagaInstance)) {
            $this->sagaInstance->startStep($step);
        }

        if (!is_null($cacheInstance)) {
            $cacheInstance->setOrchestrator($this);
        }

        try {
            $step->start();
        } catch (\SDPMlab\Anser\Exception\ActionException $e) {
            //僅捕獲 Action 例外
            $this->isSuccess = false;
        }

        $actions = $step->getStepActionList();
        foreach ($actions as $action) {
            if (!$action->isSuccess()) {
                $this->isSuccess = false;
            }
        }
    }

    /**
     * 執行所有已設定的 Step
     *
     * @return void
     */
    public function startAllStep(CacheHandlerInterface $cacheInstance = null)
    {
        $cacheInstance = $cacheInstance ?? CacheFactory::getCacheInstance();

        $this->orchestratorNumber = $this::class . '\\' . md5(json_encode($this->argsArray) . uniqid("", true)) . '\\' . date("Y-m-d H:i:s");

        // Set up the cache info/variable if developer set the cache instance.
        if (!is_null($cacheInstance)) {
            $this->cacheInitial($cacheInstance);
        }

        foreach ($this->steps as $step) {
            $this->handleSingleStep($step);

            //若有執行交易，中止 Step 的執行，並開始補償

            if ($this->isSuccess() === false) {
                if (is_null($this->sagaInstance) === false) {
                    if ($this->startOrchCompensation()) {
                        log_message(
                            "notice",
                            "The orchestrator" . $this::class . "compensate completely at " . date("Y-m-d H:i:s")
                        );
                    } else {
                        log_message(
                            "critical",
                            "The orchestrator" . $this::class . "compensate Fail at " . date("Y-m-d H:i:s")
                        );
                    }
                } else {
                    log_message(
                        "critical",
                        "The orchestrator" . $this::class . "run Fail at " . date("Y-m-d H:i:s")
                    );
                }

                break;
            }
        }

        log_message(
            "notice",
            "The orchestrator" . $this::class . "orchestrator completely at " . date("Y-m-d H:i:s")
        );

        // 當所有 Step 執行完成且都執行成功，則清除在快取的編排器
        // 並儲存 Log 進資料庫
        if (!is_null($cacheInstance)) {
            $cacheInstance->clearOrchestrator($this->serverName, $this->orchestratorNumber);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function startOrchCompensation(): ?bool
    {
        $this->isCompensationSuccess = $this->sagaInstance->startCompensation($this->steps);
        return $this->isCompensationSuccess;
    }

    /**
     * Initial the cache info before orchestrate.
     *
     * @param CacheHandlerInterface $cacheInstance
     * @return void
     */
    protected function cacheInitial(CacheHandlerInterface $cacheInstance)
    {
        if ($this->sagaInstance === null) {
            throw OrchestratorException::forSagaInstanceNotFoundInCache();
        }

        if (getenv("serverName")) {
            $this->serverName = getenv("serverName");
        }

        if ($this->serverName === null) {
            throw OrchestratorException::forServerNameNotFound();
        }

        $cacheInstance->initOrchestrator($this->serverName, $this->orchestratorNumber, $this);
    }

    /**
     * @deprecated
     */
    public function reStartRuntimeOrchestrator()
    {
        $cacheInstance = CacheFactory::getCacheInstance();

        foreach ($this->steps as $step) {
            if ($step->isSuccess() === true) {
                continue;
            }

            $this->handleSingleStep($step);

            //若有執行交易，中止 Step 的執行，並開始補償
            if (
                $this->isSuccess === false &&
                !is_null($this->sagaInstance)
            ) {
                if ($this->sagaInstance->startCompensation($this->steps)) {
                    break;
                }
            }
        }

        if (!is_null($cacheInstance)) {
            $cacheInstance->clearOrchestrator($this->orchestratorNumber);
        }

        $result = $this->defineResult();

        return $result;
    }

    /**
     * 回傳 Orchestrator 是否成功
     *
     * @return boolean
     */
    public function isSuccess()
    {
        return $this->isSuccess;
    }

    /**
     * {@inheritDoc}
     */
    public function isCompensationSuccess()
    {
        return $this->isCompensationSuccess;
    }

    /**
     * 定義 Orchestrator 的編排細節。開發者必須覆寫這個方法。
     *
     * @return void
     */
    abstract protected function definition();

    /**
     * 定義整個 Orchestrator 成功後的回傳內容。
     * 開發者可以覆寫這個方法。
     *
     * @return void
     */
    protected function defineResult()
    {
        return $this->isSuccess;
    }
}
