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
     * Undocumented variable
     *
     * @var array
     */
    protected array $failOrchestrator = [];

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
    public function reStartOrchestrator(string $className = null, mixed $serverName = null, ?bool $isRestart = false, ?string $time = null): array
    {
        if (is_null($className)) {
            throw RestarterException::forClassNameIsNull();
        }

        if (is_null($serverName) && is_null(getenv("serverName"))) {
            throw RestarterException::forServerNameIsNull();
        }

        if ($serverName === null && !is_null(getenv("serverName"))) {
            $serverName = getenv("serverName");
        }

        $serverRestartResult = [];

        if (is_array($serverName)) {
            // Handle each serverName.
            foreach ($serverName as $key => $singleServerName) {
                $runtimeOrchArray = $this->cacheInstance->getOrchestratorsByServerName($singleServerName, $className);

                $serverRestartResult[$singleServerName] = $this->handleruntimeOrchArrayCompensate(
                    $runtimeOrchArray,
                    $singleServerName
                );
            }
        } elseif (is_string($serverName)) {
            $runtimeOrchArray = $this->cacheInstance->getOrchestratorsByServerName($serverName, $className);

            $serverRestartResult[$serverName] = $this->handleruntimeOrchArrayCompensate(
                $runtimeOrchArray,
                $serverName
            );
        }

        return $serverRestartResult;
    }

    /**
     * Handle the runtime orch array from Redis.
     *
     * @param array  $runtimeOrchArray
     * @param string $serverName
     * @return array
     */
    protected function handleruntimeOrchArrayCompensate(array $runtimeOrchArray, string $serverName): array
    {
        $compensateResult = [];

        foreach ($runtimeOrchArray as $key => $runtimeOrch) {

            if (is_null($runtimeOrch->getSagaInstance())) {
                throw OrchestratorException::forSagaInstanceNotFound();
            }
            // Compensate
            $compensateResult[$runtimeOrch->getOrchestratorKey()] = $runtimeOrch->startOrchCompensation();

            if ($compensateResult[$runtimeOrch->getOrchestratorKey()] === false) {
                $this->isSuccess  = false;
                $this->failOrchestrator[$runtimeOrch::class] = $runtimeOrch;
            }


            $this->cacheInstance->clearOrchestrator($serverName, $runtimeOrch::class);
        }

        return $compensateResult;
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
        return $this->failOrchestrator;
    }
}
