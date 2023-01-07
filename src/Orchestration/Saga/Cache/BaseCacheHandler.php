<?php

namespace SDPMlab\Anser\Orchestration\Saga\Cache;

use SDPMlab\Anser\Orchestration\Saga\Cache\CacheHandlerInterface;

abstract class BaseCacheHandler implements CacheHandlerInterface
{
    public function serializeOrchestrator(array $orchestratorData): string
    {
        return "";
    }

    public function unserializeOrchestrator(string $orchestratorNumber): array
    {
        return [];
    }
}
