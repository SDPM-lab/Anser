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
     * @param array|null $serverNameList
     * @param string|null $time
     * @return bool $isSuccess Return whether re-start successfully.
     */
    public function reStartOrchestrator(
        string $className = null,
        ?array $serverNameList = [],
        ?string $time = null
    ): bool;
}
