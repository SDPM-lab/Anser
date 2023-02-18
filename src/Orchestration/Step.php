<?php

namespace SDPMlab\Anser\Orchestration;

use SDPMlab\Anser\Orchestration\StepInterface;
use SDPMlab\Anser\Service\ActionInterface;
use SDPMlab\Anser\Orchestration\Orchestrator;
use SDPMlab\Anser\Service\ConcurrentAction;
use SDPMlab\Anser\Exception\StepException;

class Step implements StepInterface
{
    /**
     * 此 step 在編排器中的執行順序
     *
     * @var integer
     */
    protected int $number;

    /**
     * 儲存 Step 中的 array，其資料結構為 陣列<別名,Action實體>
     *
     * @var array<string,ActionInterface>
     */
    protected array $actionList = [];

    /**
     * Step 是否執行成功
     */
    protected bool $isSuccess = false;

    protected $dynamicAction;

    /**
     * 儲存將此 Step 實體化的 Orchestrator
     */
    protected Orchestrator $orchestrator;

    public function __construct(Orchestrator $orchestrator, int $stepNumber)
    {
        $this->orchestrator = &$orchestrator;
        $this->number = $stepNumber;
    }

    public function __sleep()
    {
        return [
            "number",
            "actionList",
            "isSuccess",
            "dynamicAction"
        ];
    }

    /**
     * {@inheritDoc}
     *
     */
    public function setRuntimeOrchestrator(OrchestratorInterface $runtimeOrch): StepInterface
    {
        $this->orchestrator = &$runtimeOrch;
        return $this;
    }

    /**
     * 新增 Action 至 Step 中。若新增了大於一個 Action ，這些 Action 會以併行模式執行。
     *
     * 若你的 Step 所定義的 Action 倚賴 Orchestrator runtime 產生的資料。
     * 你可以在 Action 中傳入 Callable，取得 runtime 的 Orchestrator 實體。
     * 透過這個實體的定義，你能夠取得 Orchestrator 已完成的 Action 結果。
     * 在取得你所需要的 Action 與其資料後，你只需要在 Callable 中 return Action 實體即可。
     *
     * @param string $alias 別名
     * @param \SDPMlab\Anser\Service\ActionInterface|callable(\SDPMlab\Anser\Orchestration\Orchestrator):action $action
     * @return StepInterface
     */
    public function addAction(string $alias, $action): StepInterface
    {
        $this->orchestrator->checkAliasInAllSteps($alias);
        $this->actionList[$alias] = $action;
        return $this;
    }

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
    public function addDynamicActions(callable $callable): StepInterface
    {
        $this->dynamicAction = $callable;
        return $this;
    }

    /**
     * 將這個 Step 的執行與一個補償方法繫結
     * 如果 Step 中有任一 Action 執行失敗
     * 則會立即呼叫這個補償方法
     *
     * @param string $methodName
     * @return StepInterface
     */
    public function setCompensationMethod(string $methodName): StepInterface
    {
        $this->orchestrator->getSagaInstance()
            ->bindCompensationMethod($methodName, $this->number);
        return $this;
    }

    /**
     * 開始執行 Step
     *
     * @return StepInterface
     */
    public function start(): StepInterface
    {
        if (is_callable($this->dynamicAction)) {
            ($this->dynamicAction)($this->orchestrator, $this);
        }
        $actionCount = count($this->actionList);
        if ($actionCount == 1) {
            reset($this->actionList);
            $this->handleSingleAction(key($this->actionList));
        } elseif ($actionCount > 1) {
            $this->handleMultipleActions();
        } else {
            throw StepException::forNonStepAction();
        }
        return $this;
    }

    /**
     * 處理單一請求
     *
     * @param string $alias
     * @return void
     */
    protected function handleSingleAction(string $alias): void
    {
        $action = $this->actionList[$alias];
        //判斷使用者傳入的是否為 callable
        if (is_callable($action)) {
            //若是則執行 callable
            $action = $this->handleActionCallable($alias, $action);
        } else {
            //若否則檢查 action 型別
            if (!($action instanceof ActionInterface)) {
                throw StepException::forActionTypeError($alias);
            }
        }
        $action->do();
        $this->isSuccess = $action->isSuccess();
    }

    /**
     * 處理多個 Actions 時採用並行連線。
     *
     * @return void
     */
    protected function handleMultipleActions(): void
    {
        foreach ($this->actionList as $alias => $action) {
            if (is_callable($action)) {
                $this->actionList[$alias] = $this->handleActionCallable($alias, $action);
            } else {
                if (!($action instanceof ActionInterface)) {
                    throw StepException::forActionTypeError($alias);
                }
            }
        }
        $concurrentAction = new ConcurrentAction();
        $concurrentAction->setActions($this->actionList);
        $concurrentAction->send();
        foreach ($this->actionList as $alias => $action) {
            if (!$action->isSuccess()) {
                $this->isSuccess = false;
                return;
            }
        }
        $this->isSuccess = true;
    }

    /**
     * 處理 Get Action Callable
     *
     * @param string $alias 別名
     * @param callable $getActionCallable 外部傳入的 Callable
     * @return ActionInterface
     * @throws StepException
     */
    protected function handleActionCallable(
        string $alias,
        callable $getActionCallable
    ): ActionInterface {
        $action = $getActionCallable($this->orchestrator);
        if ($action instanceof ActionInterface) {
            $this->actionList[$alias] = $action;
            return $action;
        } else {
            throw StepException::forCallableActionTypeError($alias);
        }
    }

    /**
     * 取得 Action 實體
     *
     * @return ActionInterface
     */
    public function getStepAction(string $alias): ActionInterface
    {
        if (!isset($this->actionList[$alias])) {
            throw StepException::forUndefinedStepAction($alias);
        }
        return $this->actionList[$alias];
    }

    /**
     * 取得 Action 列表
     *
     * @return array<string,ActionInterface>
     */
    public function getStepActionList(): array
    {
        return $this->actionList;
    }

    /**
     * 取得失敗的 Action 列表
     *
     * @return array<string,ActionInterface>
     */
    public function getFailStepActionList(): array
    {
        $failActions = [];
        foreach ($this->actionList as $alias => $action) {
            if (!$action->isSuccess()) {
                $failActions[$alias] = $action;
            }
        }
        return $failActions;
    }

    /**
     * 判斷傳入的別名是否尚未被此 Step 使用
     *
     * @param string $alias 別名
     * @return boolean 曾未被使用則回傳 True
     */
    public function aliasNonRepeat(string $alias): bool
    {
        return !array_key_exists($alias, $this->actionList);
    }

    /**
     * 回傳 Step 在執行 Start 後是否運作完成
     *
     * @return boolean
     */
    public function isSuccess(): bool
    {
        return $this->isSuccess;
    }

    /**
     * 取得 Step 在 Orchestrator 中的執行順序
     *
     * @return integer
     */
    public function getNumber(): int
    {
        return $this->number;
    }
}
