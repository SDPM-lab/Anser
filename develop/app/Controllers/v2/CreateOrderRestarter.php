<?php

namespace App\Controllers\V2;

use App\Anser\Orchestrators\V2\CreateOrderOrchestrator;
use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use SDPMlab\Anser\Orchestration\Saga\Restarter\Restarter;
use SDPMlab\Anser\Orchestration\Saga\Cache\CacheFactory;

class CreateOrderRestarter extends BaseController
{
    use ResponseTrait;

    public function restartCreateOrderOrchestratorByServerName()
    {
        CacheFactory::initCacheDriver('redis', 'tcp://anser_redis:6379');

        $userOrchRestarter = new Restarter();

        $result = $userOrchRestarter->reStartOrchestratorsByServer(CreateOrderOrchestrator::class, 'Anser_Server_1');

        return $this->respond([
            "result" => $result
        ]);
    }

    public function restartCreateOrderOrchestratorByServerCluster()
    {
        CacheFactory::initCacheDriver('redis', 'tcp://anser_redis:6379');

        $userOrchRestarter = new Restarter();

        $result = $userOrchRestarter->reStartOrchestratorsByServer(
            CreateOrderOrchestrator::class,
            ["Anser_Server_1", "Anser_Server_2"]
        );

        return $this->respond([
            "result" => $result
        ]);
    }

    public function restartCreateOrderOrchestratorByClassName()
    {
        CacheFactory::initCacheDriver('redis', 'tcp://anser_redis:6379');

        $userOrchRestarter = new Restarter();

        $result = $userOrchRestarter->reStartOrchestratorsByClass(CreateOrderOrchestrator::class);

        return $this->respond([
            "result" => $result
        ]);
    }

    public function restartCreateOrderOrchestratorByServerNameAndNeedRestart()
    {
        CacheFactory::initCacheDriver('redis', 'tcp://anser_redis:6379');

        $userOrchRestarter = new Restarter();

        $result = $userOrchRestarter->reStartOrchestratorsByServer(CreateOrderOrchestrator::class, 'Anser_Server_1', true);

        return $this->respond([
            "result" => $result
        ]);
    }

    public function restartCreateOrderOrchestratorByClassNameAndNeedRestart()
    {
        CacheFactory::initCacheDriver('redis', 'tcp://anser_redis:6379');

        $userOrchRestarter = new Restarter();

        $result = $userOrchRestarter->reStartOrchestratorsByClass(CreateOrderOrchestrator::class, true);

        return $this->respond([
            "result" => $result
        ]);
    }
}
