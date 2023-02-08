<?php

namespace SDPMlab\Anser\Orchestration\Saga\Cache;

use SDPMlab\Anser\Orchestration\OrchestratorInterface;

interface CacheHandlerInterface
{
    /**
     * Initialize the orchestrator into the redis.
     *
     * @param string $orchestratorNumber
     * @param OrchestratorInterface $runtimeOrchestrator
     * @return CacheHandlerInterface
     */
    public function initOrchestrator(string $orchestratorNumber, OrchestratorInterface $runtimeOrchestrator): CacheHandlerInterface;

    /**
     * Set the runtime orchestor into redis after each step has finished.
     *
     * @param OrchestratorInterface $runtimeOrchestrator
     * @return CacheHandlerInterface
     */
    public function setOrchestrator(OrchestratorInterface $runtimeOrchestrator): CacheHandlerInterface;

    /**
     * When the steps of orchestrator already finished,
     * release the store resource of redis.
     *
     * @param string $orchestratorNumber
     * @return boolean
     */
    public function clearOrchestrator(?string $orchestratorNumber = null): bool;

    /**
     * Get the runtime orchestor into redis by using orchestratorNumber.
     *
     * @param string $orchestratorNumber
     * @return OrchestratorInterface
     */
    public function getOrchestrator(?string $orchestratorNumber = null): OrchestratorInterface;

    /**
     * Serialize the meta data of orchestrator,
     * and get the serialized orchestratorNumber
     *
     * @param OrchestratorInterface $orchestrator
     * @return string
     */
    public function serializeOrchestrator(OrchestratorInterface $orchestrator): string;

    /**
     * Using the serialized orchestratorNumber to unserialize,
     * and get the original orchestrator data.
     *
     * @param string $orchestratorNumber
     * @return OrchestratorInterface
     */
    public function unserializeOrchestrator(string $orchestratorNumber): OrchestratorInterface;
}
