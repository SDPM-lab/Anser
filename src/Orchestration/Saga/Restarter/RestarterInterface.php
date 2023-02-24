<?php

namespace SDPMlab\Anser\Orchestration\Saga\Restarter;

interface RestarterInterface
{
    /**
     * Re-start all pass in class name and serverName runtime orchestrators in Redis.
     * If there is serverName list pass in, then Anser will restart all runtime orchestrator in Redis.
     * Otherwise, Anser will restart the all runtime orchestrators of this restarter setting server name.
     *
     * @param string|null $className
     * @param array|string $serverName
     * @param bool|null $isRestart If pass in true in this param, the restarter will restart the runtime orchestrator after compensation.
     * Otherwise, The restarter will only run the compensation for this runtime orchestrator.
     * @param string|null $time
     * @return array
     */
    public function reStartOrchestratorsByServer(
        string $className = null,
        mixed $serverName = null,
        ?bool $isRestart  = false,
        ?string $time = null
    ): array;

    /**
     * Re-start all same className runtime orchestrator in Redis no matter what the serverName is.
     *
     * @param string|null $className
     * @param boolean|null $isRestart
     * @param string|null $time
     * @return array
     */
    public function reStartOrchestratorsByClass(
        string $className = null,
        ?bool $isRestart  = false,
        ?string $time = null
    ): array;

    /**
     * Get this restarter status.
     *
     * @return boolean
     */
    public function getIsSuccess(): bool;

    /**
     * Get the run fail Orchestrator.
     *
     * @return array
     */
    public function getFailOrchestrator(): array;
}
