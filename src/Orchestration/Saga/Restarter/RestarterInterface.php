<?php

namespace SDPMlab\Anser\Orchestration\Saga\Restarter;

use SDPMlab\Anser\Orchestration\Saga\Cache\CacheHandlerInterface;

interface RestarterInterface
{
    /**
     * Get the cache instance.
     *
     * @param CacheHandlerInterface $cacheInstance
     * @return RestarterInterface
     */
    public function setRestarterCacheInstance(CacheHandlerInterface $cacheInstance): RestarterInterface;

    /**
     * Re-start the interrupted runtime orchestrator.
     *
     * @param string|null $orchestratorNumber
     * @return void
     */
    public function reStartOrchestrator(?string $orchestratorNumber = null);
}
