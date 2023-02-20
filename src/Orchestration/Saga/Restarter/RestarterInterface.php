<?php

namespace SDPMlab\Anser\Orchestration\Saga\Restarter;

interface RestarterInterface
{
    /**
     * Re-start all pass in class name runtime orchestrators in Redis.
     * If there is serverName list pass in, then Anser will restart all runtime orchestrator in Redis.
     * Otherwise, Anser will restart the all runtime orchestrators of this restarter setting server name.
     *
     * @param string $className
     * @param array|string $serverName
     * @param bool|null $isRestart If pass in true in this param, the restarter will restart the runtime orchestrator after compensation.
     * Otherwise, The restarter will only run the compensation of this runtime orchestrator.
     * @param string|null $time
     * @return bool $isSuccess Return whether re-start successfully.
     */
    public function reStartOrchestrator(
        string $className = null,
        mixed $serverName = null,
        ?bool $isRestart = false,
        ?string $time = null
    ): bool;
}
