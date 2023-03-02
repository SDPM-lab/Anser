<?php

namespace SDPMlab\Anser\Service;

use CodeIgniter\Test\CIUnitTestCase;
use Predis\Client;
use SDPMlab\Anser\Exception\RedisException;
use SDPMlab\Anser\Orchestration\Orchestrator;
use SDPMlab\Anser\Orchestration\Saga\Cache\CacheFactory;

class TestOrchestrator extends Orchestrator
{
    protected function definition()
    {
    }
}

class RedisHandlerTest extends CIUnitTestCase
{

    /**
     * cacheInstance
     *
     * @var SDPMlab\Anser\Orchestration\Saga\Cache\Redis\RedisHandler
     */
    protected $cache;

    /**
     * The cache client
     *
     * @var Predis\Client;
     */
    protected \Predis\Client $cacheClient;

    /**
     * The original data is used at testing.
     *
     * @var array
     */
    protected array $testData_original;

    /**
     * The server name of data is different from original
     *
     * @var array
     */
    protected array $testData_server_diff;

    /**
     * The class name of data is different from original.
     *
     * @var array
     */
    protected array $testData_className_diff;


    /**
     * The class name and server name of data is different from original
     *
     * @var array
     */
    protected array $testData_server_className_diff;

    protected function setUp(): void
    {
        $this->cache        = CacheFactory::initCacheDriver('redis', 'tcp://127.0.0.1:6379');

        $this->cacheClient            = new Client([
            'scheme'   => 'tcp',
            'host'     => '127.0.0.1',
            'port'     => 6379,
            'timeout'  => 0,
        ]);

        putenv("serverName_1=server_1");
        putenv("serverName_2=server_2");

        $this->testData_original = [
            "orchestrator" => new TestOrchestrator(),
            "serverName"   => getenv("serverName_1"),
            "className"    => TestOrchestrator::class,
            "orchestratorNumber" => TestOrchestrator::class . '\\' . md5(json_encode(["foo" => "bar", "bar" => "baz"]) . uniqid("", true)) . '\\' . date("Y-m-d H:i:s")
        ];

        $this->testData_server_diff = [
            "orchestrator" => new TestOrchestrator(),
            "serverName"   => getenv("serverName_2"),
            "className"    => TestOrchestrator::class,
            "orchestratorNumber" => TestOrchestrator::class . '\\' . md5(json_encode(["foo" => "bar", "bar" => "baz"]) . uniqid("", true)) . '\\' . date("Y-m-d H:i:s")
        ];

    }

    protected function tearDown(): void
    {
        $this->cacheClient->flushall();
    }

    public function testInitOrchestrator()
    {
        $this->cache->initOrchestrator(
            $this->testData_original["serverName"],
            $this->testData_original["orchestratorNumber"],
            $this->testData_original["orchestrator"]
        );

        // Check whether the serialized runtime orch is in serverName hashMap.
        $this->assertEquals(
            $this->cache->serializeOrchestrator($this->testData_original["orchestrator"]),
            $this->cacheClient->hget(
                $this->testData_original["serverName"],
                $this->testData_original["orchestratorNumber"]
            )
        );

        // Check whether the serverName is in serverNameList set.
        $this->assertEquals(1, $this->cacheClient->sismember("serverNameList", $this->testData_original["serverName"]));

        // Check the repeat orch number.
        try {
            $this->cache->initOrchestrator(
                $this->testData_original["serverName"],
                $this->testData_original["orchestratorNumber"],
                $this->testData_original["orchestrator"]
            );
        } catch (\Exception $th) {
            $this->assertInstanceOf(RedisException::class, $th);
        }
    }

    public function testSetOrchestrator()
    {
        $this->cache->initOrchestrator(
            $this->testData_original["serverName"],
            $this->testData_original["orchestratorNumber"],
            $this->testData_original["orchestrator"]
        );

        $orchestrator = $this->testData_original["orchestrator"];

        // Something change in runtime orch.
        $orchestrator->setServerName($this->testData_original["serverName"]);

        $this->cache->setOrchestrator($orchestrator);

        $this->assertEquals(
            $this->cache->serializeOrchestrator($orchestrator),
            $this->cacheClient->hget($this->testData_original["serverName"], $this->testData_original["orchestratorNumber"])
        );
    }

    public function testGetOrchestrator()
    {
        $this->cache->initOrchestrator(
            $this->testData_original["serverName"],
            $this->testData_original["orchestratorNumber"],
            $this->testData_original["orchestrator"]
        );

        $runtimeOrch = $this->cache->getOrchestrator(
            $this->testData_original["serverName"],
            $this->testData_original["orchestratorNumber"]
        );

        $this->assertEquals($runtimeOrch, $this->testData_original["orchestrator"]);
    }

    public function testGetOrchestratorsByServerName()
    {
        $cache_1 = $this->cache->initOrchestrator(
            $this->testData_original["serverName"],
            $this->testData_original["orchestratorNumber"],
            $this->testData_original["orchestrator"]
        );

        $newOrchNumberForCache_2 = TestOrchestrator::class . '\\' . md5(json_encode(["foo" => "bar", "bar" => "baz"]) . uniqid("", true)) . '\\' . date("Y-m-d H:i:s");

        // Same orchestrator class but have different number.
        $cache_2 = $this->cache->initOrchestrator(
            $this->testData_original["serverName"],
            $newOrchNumberForCache_2,
            $this->testData_original["orchestrator"]
        );

        $expected = [
            $this->testData_original["orchestratorNumber"] =>
            $this->cache->serializeOrchestrator($this->testData_original["orchestrator"]),
            $newOrchNumberForCache_2 =>
            $this->cache->serializeOrchestrator($this->testData_original["orchestrator"]),
        ];

        $actual = $cache_1->getOrchestratorsByServerName(
            $this->testData_original["serverName"],
            $this->testData_original["className"]
        );

        $this->assertEquals($expected, $actual);
    }

    public function testGetOrchestratorsByServerNameIsNull()
    {
        $cache_1 = $this->cache->initOrchestrator(
            $this->testData_original["serverName"],
            $this->testData_original["orchestratorNumber"],
            $this->testData_original["orchestrator"]
        );

        $newOrchNumberForCache_2 = TestOrchestrator::class . '\\' . md5(json_encode(["foo" => "bar", "bar" => "baz"]) . uniqid("", true)) . '\\' . date("Y-m-d H:i:s");

        // Same orchestrator class but have different number.
        $cache_2 = $this->cache->initOrchestrator(
            $this->testData_original["serverName"],
            $newOrchNumberForCache_2,
            $this->testData_original["orchestrator"]
        );

        $this->assertEquals(
            null,
            $cache_1->getOrchestratorsByServerName(
                "serverNotFound",
                $this->testData_original["className"]
            )
        );

        $this->assertEquals(
            [],
            $cache_1->getOrchestratorsByServerName(
                $this->testData_original["serverName"],
                "classNotFound"
            )
        );
    }

    public function testGetOrchestratorsByClassName()
    {
        $cache_1 = $this->cache->initOrchestrator(
            $this->testData_original["serverName"],
            $this->testData_original["orchestratorNumber"],
            $this->testData_original["orchestrator"]
        );

        $newOrchNumberForCache_2 = TestOrchestrator::class . '\\' . md5(json_encode(["foo" => "bar", "bar" => "baz"]) . uniqid("", true)) . '\\' . date("Y-m-d H:i:s");

        // Same orchestrator class but have different number.
        $cache_2 = $this->cache->initOrchestrator(
            $this->testData_original["serverName"],
            $newOrchNumberForCache_2,
            $this->testData_original["orchestrator"]
        );

        $cache_3 = $this->cache->initOrchestrator(
            $this->testData_server_diff["serverName"],
            $this->testData_server_diff["orchestratorNumber"],
            $this->testData_server_diff["orchestrator"]
        );

        $expected = [
            $this->testData_original["serverName"] => [
                $this->testData_original["orchestratorNumber"] =>
                $this->cache->serializeOrchestrator($this->testData_original["orchestrator"]),
                $newOrchNumberForCache_2 =>
                $this->cache->serializeOrchestrator($this->testData_original["orchestrator"]),
                
            ],
            $this->testData_server_diff["serverName"] => [
                $this->testData_server_diff["orchestratorNumber"] =>
                $this->cache->serializeOrchestrator($this->testData_server_diff["orchestrator"]),
            ],
        ];

        $actual = $cache_1->getOrchestratorsByClassName(
            $this->testData_original["className"]
        );

        $this->assertEquals($expected, $actual);
    }

    public function testClearOrchestrator()
    {
        $this->cache->initOrchestrator(
            $this->testData_original["serverName"],
            $this->testData_original["orchestratorNumber"],
            $this->testData_original["orchestrator"]
        );

        $this->cache->clearOrchestrator(
            $this->testData_original["serverName"],
            $this->testData_original["orchestratorNumber"]
        );

        $this->assertEquals(
            0,
            $this->cacheClient->hget(
                $this->testData_original["serverName"],
                $this->testData_original["orchestratorNumber"]
            )
        );

        $this->assertEquals(
            0,
            $this->cacheClient->sismember("serverNameList", $this->testData_original["serverName"])
        );
    }
}
