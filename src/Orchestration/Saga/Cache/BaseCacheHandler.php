<?php

namespace SDPMlab\Anser\Orchestration\Saga\Cache;

use SDPMlab\Anser\Orchestration\Saga\Cache\CacheHandlerInterface;
use SDPMlab\Anser\Orchestration\OrchestratorInterface;
use Zumba\JsonSerializer\JsonSerializer;

abstract class BaseCacheHandler implements CacheHandlerInterface
{
    /**
     * The serializer.
     *
     * @var JsonSerializer
     */
    public JsonSerializer $serializer;

    public function __construct()
    {
        $this->serializer = new JsonSerializer();
    }

    public function serializeOrchestrator(OrchestratorInterface $orchestrator): string
    {
        return $this->serializer->serialize($orchestrator);
    }

    public function unserializeOrchestrator(string $serializedOrchestrator): OrchestratorInterface
    {
        return $this->serializer->unserialize($serializedOrchestrator);
    }
}
