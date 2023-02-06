<?php

namespace SDPMlab\Anser\Orchestration\Saga\Restarter;

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
    }

    /**
     * {@inheritDoc}
     */
    public function setRestarterCacheInstance(CacheHandlerInterface $cacheInstance): RestarterInterface
    {
        $this->cacheInstance = $cacheInstance;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function reStartOrchestrator(?string $orchestratorNumber = null)
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
        dd($this->runtimeOrchestrator);
    }
}