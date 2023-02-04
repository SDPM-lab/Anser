<?php

namespace App\Controllers\V1;

use App\Controllers\BaseController;
use stdClass;
use Zumba\JsonSerializer\JsonSerializer;
use SDPMlab\Anser\Orchestration\Saga\Cache\CacheFactory;
use SDPMlab\Anser\Orchestration\Orchestrator;
use App\Anser\Orchestrators\UserOrchestrator;
use Pingyi\JsonSerializer\ClosureSerializer\ClosureSerializer;

class Serialize extends BaseController
{
    public function testJsonSerializer()
    {
        $toBeSerialized = new stdClass();

        $toBeSerialized->data = [1, 2, 3];
        $toBeSerialized->name = 'N$ck';
        $toBeSerialized->closure = function () {
            return "Closure";
        };

        $jsonSerializer = new JsonSerializer(new ClosureSerializer());

        $serialized   = $jsonSerializer->serialize($toBeSerialized);
        $unserialized = $jsonSerializer->unserialize($serialized);

        var_dump(call_user_func($unserialized->closure));
    }
}
