<?php

namespace App\Controllers\V1;

use App\Controllers\BaseController;
use stdClass;
use Zumba\JsonSerializer\JsonSerializer;
use SDPMlab\Anser\Orchestration\Saga\Cache\CacheFactory;
use SDPMlab\Anser\Orchestration\Orchestrator;

class Serialize extends BaseController
{
    public function testJsonSerializer()
    {
        $toBeSerialized = new stdClass();
        $toBeSerialized->data = [1, 2, 3];
        $toBeSerialized->name = 'N$ck';

        // If the serialized object included the closure function, 
        // you'll need to use the SuperClosure\Serializer repo.
        // This interface is deprecated, waiting for the issue https://github.com/zumba/json-serializer/issues/49.
        // $superClosure = new Serializer();

        // If just need to serialize the object, 
        // you just need to use Zumba\JsonSerializer\JsonSerializer repo.
        // $jsonSerializer = new JsonSerializer($superClosure);
        // $serialized = $jsonSerializer->serialize($toBeSerialized);
        // $superClosure = new \SuperClosure\Serializer();
        $jsonSerializer = new JsonSerializer();

        $serialized = $jsonSerializer->serialize($toBeSerialized);
        $unserialized = $jsonSerializer->unserialize($serialized);

        var_dump($toBeSerialized == $unserialized);
        var_dump($unserialized->data);
        var_dump($unserialized->name);
    }
}
