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
    public function reStartOrchestrator(?string $className = null, mixed $serverName = null, ?bool $isRestart = false, ?string $time = null): bool
    {
        if (is_null($className)) {
            throw RestarterException::forClassNameIsNull();
        }

        if (is_null($serverName)) {
            throw RestarterException::forClassNameIsNull();
        }

        if (is_null($this->runtimeOrchestrator->sagaInstance)) {
            throw OrchestratorException::forSagaInstanceNotFound();
        }


        if (is_array($serverName)) {
            foreach ($serverName as $key => $serverName) {
                $this->cacheInstance->getOrchestrator($cacheKey);
            }
        }


        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function reStartOrchestratorOld(?string $orchestratorNumber = null)
    {
        if (is_null($orchestratorNumber) && is_null($this->orchestratorNumber)) {
            throw RestarterException::forOrchestratorNumberNotFound();
        } elseif (!is_null($this->orchestratorNumber) && is_null($orchestratorNumber)) {
            $orchestratorNumber = $this->orchestratorNumber;
        }

        if (is_null($this->cacheInstance)) {
            throw RestarterException::forCacheInstanceNotFound();
        }

        $this->runtimeOrchestrator = $this->cacheInstance->getOrchestrator($orchestratorNumber);

        $this->runtimeOrchestrator->reStartRuntimeOrchestrator();
    }
}
