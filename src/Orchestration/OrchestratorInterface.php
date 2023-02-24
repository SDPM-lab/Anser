<?php

namespace SDPMlab\Anser\Orchestration;

use SDPMlab\Anser\Orchestration\StepInterface;
use SDPMlab\Anser\Exception\OrchestratorException;
use SDPMlab\Anser\Service\ActionInterface;
use SDPMlab\Anser\Orchestration\Saga\Cache\CacheHandlerInterface;
use SDPMlab\Anser\Orchestration\Saga\SagaInterface;

interface OrchestratorInterface
{
    /**
     * 設定一個新的 Step
     *
     * @return StepInterface
     */
    public function setStep(): StepInterface;

    /**
     * Get the step instance from orchestrator Step array.
     *
     * @param integer $index
     * @return StepInterface
     */
    public function getStep(int $index): StepInterface;

    /**
     * Set this orchestrator server name.
     * (Using in Cache scanerio.)
     *
     * @param string $serverName
     * @return OrchestratorInterface
     */
    public function setServerName(string $serverName): OrchestratorInterface;

    /**
     * 設定快取編排器之索引
     *
     * @return string|null
     */
    public function getOrchestratorNumber(): ?string;

    /**
     * Get the saga instance of this orchestrator.
     *
     * @return SagaInterface|null
     */
    public function getSagaInstance(): ?SagaInterface;

    /**
     * 標註交易開始，由此之後發生的 Step 失敗或任何程式例外將觸發 Rollback
     *
     * @param string $transactionClass 傳入交易所需之類別名稱
     * @return OrchestratorInterface
     */
    public function transStart(string $transactionClass): OrchestratorInterface;

    /**
     * 標註交易結束，由此之後發生的 Step 失敗皆不觸發 Rollback
     *
     * @return OrchestratorInterface
     */
    public function transEnd(): OrchestratorInterface;

    /**
     * 判斷在目前被設定的 Step 之中，傳入的別名從來沒有被使用過。
     *
     * @param string $alias
     * @return boolean
     * @throws OrchestratorException
     */
    public function checkAliasInAllSteps(string $alias): bool;


    /**
     * 取得目前 Orchestrator Steps 中符合傳入別名的 Action 實體
     *
     * @param string $alias
     * @return ActionInterface
     * @throws OrchestratorException
     */
    public function getStepAction(string $alias): ActionInterface;

    /**
     * 取得在所有 Step 中失敗的 Action 列表，若全數成功則回傳空陣列。
     * 其資料結構為 陣列<別名,Action實體>
     *
     * @return array<string,\SDPMlab\Anser\Service\ActionInterface>
     */
    public function getFailActions(): array;

    /**
     * 執行 Orchestrator
     *
     * @param mixed ...$args
     * @return void
     */
    public function build(...$args);

    /**
     * Re-start the runtime orchestrator.
     *
     * @return void
     */
    public function reStartRuntimeOrchestrator();

    /**
     * 回傳 Orchestrator 是否成功
     *
     * @return boolean
     */
    public function isSuccess();

    /**
     * Check whether the saga compensation run successfully.
     *
     * @return boolean
     */
    public function isCompensationSuccess();

    /**
     * Start to saga compensate.
     *
     * @return boolean|null
     */
    public function startOrchCompensation(): ?bool;
}
