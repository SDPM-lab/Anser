<?php

namespace SDPMlab\Anser\Service;

use CodeIgniter\Test\CIUnitTestCase;
use SDPMlab\Anser\Orchestration\Saga\Cache\NativeRedis\NativeRedisHandler;
use SDPMlab\Anser\Orchestration\Saga\Cache\NativeRedis\config as NativeRedisConfig;
use SDPMlab\Anser\Exception\RedisException;
use SDPMlab\Anser\Orchestration\Orchestrator;
use SDPMlab\Anser\Orchestration\Saga\Cache\CacheFactory;

class TestOrchestratorForNative extends Orchestrator
{
    protected function definition()
    {
    }
}

class NativeRedisHandlerTest extends CIUnitTestCase
{

    /**
     * cacheInstance
     *
     * @var SDPMlab\Anser\Orchestration\Saga\Cache\NativeRedis\NativeRedisHandler
     */
    protected $cache;

    /**
     * The cache client (native php redis)
     *
     * @var \Redis;
     */
    protected \Redis $cacheClient;

    protected function setUp(): void
    {
        $this->cache  = CacheFactory::initCacheDriver(
            CacheFactory::CACHE_DRIVER_NATIVE_REDIS, 
            new NativeRedisConfig(
                host: "anser_redis",
                port: 6379,
                db: 1,
                useDefaultConnection:true,
                serverName: 'server_1'
        ));

        $this->cacheClient = new \Redis();
        $this->cacheClient->connect(
            'anser_redis',
            6379,
            0
        );
        $this->cacheClient->select(1);

        putenv("serverName_1=server_1");
    }

    protected function tearDown(): void
    {
        $this->cacheClient->flushall();
    }

    public function testInitOrchestrator()
    {
        $runTimeOrch = new TestOrchestratorForNative();
        $runTimeOrch->build();

        $this->cache->initOrchestrator($runTimeOrch);

        // Check whether the serialized runtime orch is in serverName hashMap.
        $this->assertEquals(
            $this->cache->serializeOrchestrator($runTimeOrch),
            $this->cacheClient->hget(
                getenv("serverName_1"),
                $runTimeOrch->getOrchestratorNumber()
            )
        );

        // Check whether the serverName is in serverNameList set.
        $this->assertEquals(1, $this->cacheClient->sismember("serverNameList", getenv("serverName_1")));

        // // Check the repeat orch number.
        $this->expectException(RedisException::class);
        $this->expectExceptionMessage("此編排器編號- {$runTimeOrch->getOrchestratorNumber()} 已在 Redis 內被初始化，請重新輸入。");
        $this->cache->initOrchestrator($runTimeOrch);
    }

    public function testSetOrchestrator()
    {
        $orchestrator = new TestOrchestratorForNative();
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
        $orchestrator = new TestOrchestratorForNative();
        $orchestrator->build();
        
        $this->cache->initOrchestrator($orchestrator);

        $runtimeOrch = $this->cache->getOrchestrator(
            $orchestrator->getOrchestratorNumber()
        );

        $this->assertEquals($runtimeOrch, $orchestrator);
    }

    public function testGetOrchestrators()
    {
        $orchestrator = new TestOrchestratorForNative();
        $orchestrator->build();
        
        $this->cache->initOrchestrator($orchestrator);

        $runtimeOrch = $this->cache->getOrchestrators(TestOrchestratorForNative::class, getenv("serverName_1"));

        $this->assertEquals($runtimeOrch[$orchestrator->getOrchestratorNumber()], $orchestrator);
    }

    public function testGetOrchestratorsByClassName()
    {
        $orchestrator = new TestOrchestratorForNative();
        $orchestrator->build();
        
        $this->cache->initOrchestrator($orchestrator);

        $runtimeOrch = $this->cache->getOrchestrators(TestOrchestratorForNative::class);

        $this->assertEquals($runtimeOrch[$orchestrator->getOrchestratorNumber()], $orchestrator);
    }

    public function testGetServersOrchestrator()
    {
        $orchestrator = new TestOrchestratorForNative();
        $orchestrator->build();

        $this->cache->initOrchestrator($orchestrator);

        $runtimeOrch = $this->cache->getServersOrchestrator(TestOrchestratorForNative::class);

        $this->assertEquals(
            $runtimeOrch[getenv("serverName_1")][$orchestrator->getOrchestratorNumber()], 
            $orchestrator
        );
    }
    
    public function testClearOrchestrator()
    {
        $orchestrator = new TestOrchestratorForNative();
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
}
