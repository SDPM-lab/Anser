<?php

namespace SDPMlab\Anser\Orchestration\Saga\Restarter;

use SDPMlab\Anser\Exception\OrchestratorException;
use SDPMlab\Anser\Orchestration\Saga\Restarter\RestarterInterface;
use SDPMlab\Anser\Orchestration\Saga\Cache\CacheHandlerInterface;
use SDPMlab\Anser\Orchestration\OrchestratorInterface;
use SDPMlab\Anser\Orchestration\Saga\Cache\CacheFactory;
use SDPMlab\Anser\Exception\RestarterException;

class Restarter implements RestarterInterface
{
    /**
     * The cache instance.
     *
     * @var CacheHandlerInterface
     */
    protected $cacheInstance;

    /**
     * The orchestrator number
     *
     * @var string|null
     */
    protected $orchestratorNumber = null;

    /**
     * The runtimeOrchestrator, getten from cache.
     *
     * @var OrchestratorInterface
     */
    protected $runtimeOrchestrator;

    /**
     * Check this restarter whether is success.
     *
     * @var boolean
     */
    protected $isSuccess = false;

    /**
     * The result of server restart.
     *
     * @var array
     */
    protected $serverRestartResult = [];

    /**
     * Undocumented variable
     *
     * @var array
     */
    protected array $failCompensationOrchestrator = [];

    /**
     * Undocumented variable
     *
     * @var array
     */
    protected array $failRestartOrchestrator = [];

    public function __construct(?string $orchestratorNumber = null)
    {
        if (!is_null($orchestratorNumber)) {
            $this->orchestratorNumber = $orchestratorNumber;
        }

        $this->cacheInstance = CacheFactory::getCacheInstance();
    }

    /**
     * {@inheritDoc}
     */
    public function reStartOrchestratorsByServer(
        string $className = null,
        $serverName = null,
        ?bool $isRestart  = false,
        ?string $time     = null
    ): array {
        if (is_null($className)) {
            throw RestarterException::forClassNameIsNull();
        }

        if (is_null($serverName) && is_null(getenv("serverName"))) {
            throw RestarterException::forServerNameIsNull();
        }

        if (is_array($serverName)) {
            foreach ($serverName as $key => $singleServerName) {
                $runtimeOrchArray = $this->cacheInstance->getOrchestrators($className, $singleServerName);

                if ($runtimeOrchArray === null) {
                    $this->serverRestartResult[$singleServerName] = [
                        "compensateResult" => "編排器名稱 - {$className} 不存在於 {$singleServerName} 內。"
                    ];
                    continue;
                } else {
                    $this->handleSingleServerRestart($singleServerName, $runtimeOrchArray, $isRestart);
                }
            }
        } elseif (is_string($serverName)) {
            $runtimeOrchArray = $this->cacheInstance->getOrchestrators($className, $serverName);

            if ($runtimeOrchArray === null) {
                $this->serverRestartResult[$serverName] = [
                    "compensateResult" => "編排器名稱 - {$className} 不存在於 {$serverName} 內。"
                ];
            } else {
                $this->handleSingleServerRestart($serverName, $runtimeOrchArray, $isRestart);
            }
        }
        return $this->serverRestartResult;
    }

    /**
     * {@inheritDoc}
     */
    public function reStartOrchestratorsByClass(
        ?string $className = null,
        ?bool $isRestart   = false,
        ?string $time      = null
    ): array {
        if (is_null($className)) {
            throw RestarterException::forClassNameIsNull();
        }

        $serverNameAndRuntimeOrchArray = $this->cacheInstance->getServersOrchestrator($className);

        foreach ($serverNameAndRuntimeOrchArray as $serverName => $runtimeOrchArray) {
            $this->handleSingleServerRestart($serverName, $runtimeOrchArray, $isRestart);
        }

        return $this->serverRestartResult;
    }

    /**
     * Handle the single server restart. Two step included:
     * First, it will run the compensate of the runtime Orchestrator.
     * And second is if the $isRestart is true,
     * it will handle the restart all step of the runtime Orch and store the result.
     *
     * @param string $serverName
     * @param array $runtimeOrchArray
     * @param boolean $isRestart
     * @return void
     */
    protected function handleSingleServerRestart(string $serverName, array $runtimeOrchArray, bool $isRestart)
    {
        // First, it will run the compensate of the runtime Orchestrator.
        $compensateResult = $this->handleRuntimeOrchArrayCompensate(
            $runtimeOrchArray,
            $serverName
        );

        $compensateUserResult = [];
        //only need orchestratorNumber and compensateResult
        foreach ($compensateResult as $orchestratorNumber => $data) {
            $compensateUserResult[$orchestratorNumber] = $data["compensateResult"];
        }
        // Store the compensate result.
        $this->serverRestartResult[$serverName] = [
            "compensateResult" => $compensateUserResult
        ];

        // If the $isRestart is true, it will handle the restart all step of the runtime Orch and store the result.
        if ($isRestart === true) {
            $rebuiltResult = $this->handleRuntimeOrchArrayRestart($compensateResult); 
            $rebuiltUserResult = [];
            //only need orchestratorNumber and restartResult
            foreach ($rebuiltResult as $orchestratorNumber => $data) {
                $rebuiltUserResult[$orchestratorNumber] = $data["restartResult"];
            }
            $this->serverRestartResult[$serverName] = [
                "restartResult" => $rebuiltUserResult
            ];
        }
    }

    /**
     * Handle the runtime orch array from Redis.
     *
     * @param OrchestratorInterface[]  $runtimeOrchArray
     * @param string $serverName
     * @return array
     */
    protected function handleRuntimeOrchArrayCompensate(array $runtimeOrchArray, string $serverName): array
    {
        $compensateResult = [];

        foreach ($runtimeOrchArray as $orchestratorNumber => $runtimeOrch) {
            if (is_null($runtimeOrch->getSagaInstance())) {
                throw OrchestratorException::forSagaInstanceNotFound();
            }

            // Compensate
            $compensateResult[$runtimeOrch->getOrchestratorNumber()] = [
                "compensateResult"    => $runtimeOrch->startOrchCompensation(),
                "runtimeOrchestrator" => $runtimeOrch
            ];

            if ($compensateResult[$runtimeOrch->getOrchestratorNumber()] === false) {
                $this->isSuccess  = false;
                $this->failCompensationOrchestrator[$runtimeOrch->getOrchestratorNumber()] = $runtimeOrch;
            }

            $this->cacheInstance->clearOrchestrator($runtimeOrch);
        }

        return $compensateResult;
    }

    /**
     * Handle the runtime Orch Array restart if that compensate successfully.
     *
     * @param array $compensateResultArray
     * @return array
     */
    protected function handleRuntimeOrchArrayRestart(array $compensateResultArray): array
    {
        $restartResult = [];

        foreach ($compensateResultArray as &$compensateResult) {
            if ($compensateResult["compensateResult"] === false) {
                continue;
            }

            /** @var OrchestratorInterface */
            $runtimeOrch = $compensateResult["runtimeOrchestrator"];
            $runtimeOrch->reBuild();

            $restartResult[$runtimeOrch->getOrchestratorNumber()] = [
                "restartResult" => $runtimeOrch->isSuccess()
            ];

            if ($runtimeOrch->isSuccess() === false) {
                $restartResult[$runtimeOrch->getOrchestratorNumber()] = [
                    "failStep" => $runtimeOrch->getFailActions()
                ];
            }
        }

        return $restartResult;
    }

    /**
     * {@inheritDoc}
     */
    public function getIsSuccess(): bool
    {
        return $this->isSuccess;
    }

    /**
     * {@inheritDoc}
     */
    public function getFailOrchestrator(): array
    {
        return $this->failCompensationOrchestrator;
    }
}
