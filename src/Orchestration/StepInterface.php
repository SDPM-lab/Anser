<?php

namespace SDPMlab\Anser\Orchestration;

use SDPMlab\Anser\Service\ActionInterface;

interface StepInterface
{
    /**
     * 新增 Action 至 Step 中。若新增了大於一個 Action ，這些 Action 會以併行模式執行。
     *
     * 若你的 Step 所定義的 Action 倚賴 Orchestrator runtime 產生的資料。
     * 你可以在 Action 中傳入 Callable，取得 runtime 的 Orchestrator 實體。
     * 透過這個實體的定義，你能夠取得 Orchestrator 已完成的 Action 結果。
     * 在取得所需要的 Action 與其資料後，只需要在 Callable 中 return Action 實體即可。
     *
     * @param string $alias 別名
     * @param \SDPMlab\Anser\Service\ActionInterface|callable(\SDPMlab\Anser\Orchestration\Orchestrator):void $action
     * @return StepInterface
     */
    public function addAction(string $alias, $action): StepInterface;

    /**
     * 新增動態Action。
     *
     * 如果你不確定 Action 會產生的數量，則可以使用這個方法動態新增下一個 Step 所需的 Actions 。
     * 你可以在這個方法中傳入 Callable。
     * 這個 Callable 能夠取得 runtime 的 Orchestrator 實體以及 runtime 的 Step 實體。
     * 你可以取得 Orchestrator 已經執行完畢的 Step 中的資料。
     * 透過動態的資料，再自行定義自己的動態 Action。
     *
     * @param callable(\SDPMlab\Anser\Orchestration\Orchestrator) $callable
     * @return StepInterface
     */
    public function addDynamicActions(callable $callable): StepInterface;

    /**
     * 替本次 Step 提供一組補償用的 Method。
     * 此 Method 必須為 transactionClass 的成員方法。
     *
     * @param string $methodName 在
     * @return StepInterface
     */
    public function setCompensationMethod(string $methodName): StepInterface;

    /**
     * After de-serialize, re-setup the runtime orchestrator.
     *
     * @param OrchestratorInterface $runtimeOrch
     * @return StepInterface
     */
    public function setRuntimeOrchestrator(OrchestratorInterface $runtimeOrch): StepInterface;

    /**
     * 開始執行 Step 內被設定的 Action
     *
     * @return StepInterface
     */
    public function start(): StepInterface;

    /**
     * 取得 Action 實體
     *
     * @return ActionInterface
     */
    public function getStepAction(string $alias): ActionInterface;

    /**
     * 取得 Action 列表
     *
     * @return array<string,ActionInterface>
     */
    public function getStepActionList(): array;

    /**
     * 取得失敗的 Action 列表
     *
     * @return array<string,ActionInterface>
     */
    public function getFailStepActionList(): array;

    /**
     * 判斷傳入的別名是否尚未被此 Step 使用
     *
     * @param string $alias 別名
     * @return boolean 曾未被使用則回傳 True
     */
    public function aliasNonRepeat(string $alias): bool;

    /**
     * 回傳 Step 在執行 Start 後是否運作完成
     *
     * @return boolean
     */
    public function isSuccess(): bool;

    /**
     * 取得 Step 在 Orchestrator 中的執行順序
     *
     * @return integer
     */
    public function getNumber(): int;
}
