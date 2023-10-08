<?php

namespace SDPMlab\Anser\Service;

use CodeIgniter\Test\CIUnitTestCase;
use Predis\Client;
use SDPMlab\Anser\Exception\RedisException;
use SDPMlab\Anser\Orchestration\Orchestrator;
use SDPMlab\Anser\Orchestration\Saga\Cache\CacheFactory;
use SDPMlab\Anser\Orchestration\Saga\Cache\Redis\config;
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
        $this->cache = CacheFactory::initCacheDriver(
            CacheFactory::CACHE_DRIVER_PREDIS, 
            new config(
                host: "anser_redis",
                port: 6379,
                timeout: 0,
                db: 1,
                serverName: 'server_1'
            )
        );

        $this->cacheClient = new Client([
            'scheme'   => 'tcp',
            'host'     => 'anser_redis',
            'port'     => 6379,
            'timeout'  => 0,
        ]);
        $this->cacheClient->select(1);

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
        $orchestrator = new TestOrchestrator();
        $orchestrator->build();

        $this->cache->initOrchestrator($orchestrator);

        // Check whether the serialized runtime orch is in serverName hashMap.
        $this->assertEquals(
            $this->cache->serializeOrchestrator($orchestrator),
            $this->cacheClient->hget(
                getenv('serverName_1'),
                $orchestrator->getOrchestratorNumber()
            )
        );

        // Check whether the serverName is in serverNameList set.
        $this->assertEquals(1, $this->cacheClient->sismember("serverNameList", getenv('serverName_1')));

        // Check the repeat orch number.
        $this->expectException(RedisException::class);
        $this->expectExceptionMessage("此編排器編號- {$orchestrator->getOrchestratorNumber()} 已在 Redis 內被初始化，請重新輸入。");
        $this->cache->initOrchestrator($orchestrator);
    }

    public function testSetOrchestrator()
    {
        $orchestrator = new TestOrchestrator();
        $orchestrator->build();

        $this->cache->initOrchestrator($orchestrator);

        // Something change in runtime orch.
        $orchestrator->setServerName(getenv("serverName_1"));

        $this->cache->setOrchestrator($orchestrator);

        $this->assertEquals(
            $this->cache->serializeOrchestrator($orchestrator),
            $this->cacheClient->hget(getenv("serverName_1"), $orchestrator->getOrchestratorNumber())
        );
    }

    public function testGetOrchestrator()
    {
        $orchestrator = new TestOrchestrator();
        $orchestrator->build();
        
        $this->cache->initOrchestrator($orchestrator);

        $runtimeOrch = $this->cache->getOrchestrator(
            $orchestrator->getOrchestratorNumber()
        );

        $this->assertEquals($runtimeOrch, $orchestrator);
    }

    
    public function testGetOrchestratorNotFound()
    {
        $notExistOrchNum = TestOrchestrator::class . '\\' . md5(json_encode(["foo" => "bar", "bar" => "baz"]) . uniqid("", true)) . '\\' . date("Y-m-d H:i:s");

        $this->expectException(RedisException::class);
        $this->expectExceptionMessage("Redis 內找不到此編排器編號- {$notExistOrchNum} ，請重新輸入。");
        $this->cache->getOrchestrator($notExistOrchNum);
    }

    public function testGetOrchestrators()
    {
        $orchestrator = new TestOrchestrator();
        $orchestrator->build();
        
        $this->cache->initOrchestrator($orchestrator);

        $runtimeOrch = $this->cache->getOrchestrators(TestOrchestrator::class, getenv("serverName_1"));

        $this->assertEquals($runtimeOrch[$orchestrator->getOrchestratorNumber()], $orchestrator);
    }

    public function testGetOrchestratorsByClassName()
    {
        $orchestrator = new TestOrchestrator();
        $orchestrator->build();
        
        $this->cache->initOrchestrator($orchestrator);

        $runtimeOrch = $this->cache->getOrchestrators(TestOrchestrator::class);

        $this->assertEquals($runtimeOrch[$orchestrator->getOrchestratorNumber()], $orchestrator);
    }

    public function testGetServersOrchestrator()
    {
        $orchestrator = new TestOrchestrator();
        $orchestrator->build();

        $this->cache->initOrchestrator($orchestrator);

        $runtimeOrch = $this->cache->getServersOrchestrator(TestOrchestrator::class);

        $this->assertEquals(
            $runtimeOrch[getenv("serverName_1")][$orchestrator->getOrchestratorNumber()], 
            $orchestrator
        );
    }
    
    public function testClearOrchestrator()
    {
        $orchestrator = new TestOrchestrator();
        $orchestrator->build();

        $this->cache->initOrchestrator($orchestrator);

        $this->cache->clearOrchestrator($orchestrator);

        $this->assertEquals(
            0,
            $this->cacheClient->hget(
                getenv("serverName_1"),
                $orchestrator->getOrchestratorNumber()
            )
        );

        $this->assertEquals(
            0,
            $this->cacheClient->sismember("serverNameList", getenv("serverName_1"))
        );
    }

    public function testClearOrchestratorNotFound()
    {
        $orchestrator = new TestOrchestrator();
        $orchestrator->build();

        $this->cache->initOrchestrator($orchestrator);
        $this->cacheClient->hdel(
            getenv("serverName_1"),
            $orchestrator->getOrchestratorNumber()
        );

        $this->expectException(RedisException::class);
        $this->expectExceptionMessage("Redis 內找不到此編排器編號- {$orchestrator->getOrchestratorNumber()} ，請重新輸入。");
        $this->cache->clearOrchestrator($orchestrator);
    }
}
