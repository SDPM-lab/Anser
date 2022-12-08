<?php

namespace SDPMlab\Anser\Orchestration\Saga\Cache;

interface CacheHandlerInterface
{
    /**
     * Initialize the orchestrator status into the cache.
     */
    public function initOrchestrator(string $orchestratorNumber): CacheHandlerInterface;

    /**
     * Set the orchestor status after each step has finished.
     */
    public function setOrchestratorStatus(string $orchestratorNumber, string $orchestratorStatus): CacheHandlerInterface;

    /**
     * Get the orchestor status by usint orchestratorNumber.
     */
    public function getOrchestratorStatus(string $orchestratorNumber): string;

    /**
     * Serialize the meta data of orchestrator,
     * and get the serialized orchestratorNumber
     *
     * @return string $orchestratorNumber
     */
    public function serializeOrchestrator(array $orchestratorData): string;

    /**
     * Using the serialized orchestratorNumber to unserialize,
     * and get the original orchestrator data.
     *
     * @return array $orchestratorData
     */
    public function unserializeOrchestrator(string $orchestratorNumber): array;
}
