<?php

namespace SDPMlab\Anser\Orchestration;

use PhpParser\Node\Stmt\TryCatch;
use SDPMlab\Anser\Orchestration\Step;
use SDPMlab\Anser\Orchestration\StepInterface;
use SDPMlab\Anser\Exception\OrchestratorException;
use SDPMlab\Anser\Orchestration\Saga\SagaInterface;
use SDPMlab\Anser\Orchestration\Saga\Saga;
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
     * SAGA 實體
     */
    protected ?SagaInterface $sagaInstance = null;

    /**
     * 快取實體
     *
     * @var CacheHandlerInterface|null
     */
    protected ?CacheHandlerInterface $cacheInstance = null;

    /**
     * 編排器快取 key
     *
     * @var string|null
     */
    protected ?string $cacheOrchestratorNumber = null;

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

    public function setCacheInstance(CacheHandlerInterface $cacheInstance): OrchestratorInterface
    {
        $this->cacheInstance = $cacheInstance;
        return $this;
    }

    public function getCacheInstance(): CacheHandlerInterface
    {
        return $this->cacheInstance;
    }

    public function setCacheOrchestratorKey(string $orchestratorNumber): OrchestratorInterface
    {
        $this->cacheOrchestratorNumber = $orchestratorNumber;
        return $this;
    }

    /**
     * 取得 Saga 實體
     *
     * @return SagaInterface
     */
    public function getSagaInstance(): SagaInterface
    {
        return $this->sagaInstance;
    }

    public function transStart(string $transactionClass): OrchestratorInterface
    {
        $startStepNumber = count($this->steps) > 0 ? count($this->steps)-1 : 0;
        $this->sagaInstance = new Saga(
            $transactionClass,
            $this,
            $startStepNumber
        );
        return $this;
    }

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
        $this->definition(...$args);

        $this->startAllStep();

        // try {
        //     $this->startAllStep();
        // } catch (\Exception $e) {
        //     if (
        //         $this->isSuccess === false &&
        //         !is_null($this->sagaInstance)
        //     ) {
        //         $this->sagaInstance->startCompensation($this->steps);
        //     }
        // }

        $result = $this->defineResult();
        return $result;
    }

    /**
     * 執行所有已設定的 Step
     *
     * @return void
     */
    protected function startAllStep()
    {
        // 若有設定快取實體，則在剛開始將此次的編排器註冊進快取裡。
        if (!is_null($this->cacheInstance)) {
            if ($this->cacheOrchestratorNumber === null) {
                throw OrchestratorException::forCacheOrchestratorNotDefine();
            }

            $this->cacheInstance->initOrchestrator($this->cacheOrchestratorNumber, $this);
        }

        foreach ($this->steps as $step) {
            // 將當前 Step 紀錄於 Saga
            if (!is_null($this->sagaInstance)) {
                $this->sagaInstance->startStep($step);
            }

            if (!is_null($this->cacheInstance)) {
                $this->cacheInstance->setOrchestrator($this);
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

        // 當所有 Step 執行完成且都執行成功，則清除在快取的編排器
        // 並儲存 Log 進資料庫
        if ($this->isSuccess() === true && !is_null($this->cacheInstance)) {
            $this->cacheInstance->clearOrchestrator();
        }
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
