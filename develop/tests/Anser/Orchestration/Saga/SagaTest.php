<?php

namespace SDPMlab\Anser\Service;

use CodeIgniter\Test\CIUnitTestCase;
use Psr\Http\Message\ResponseInterface;
use SDPMlab\Anser\Orchestration\Orchestrator;
use SDPMlab\Anser\Orchestration\Saga\Saga;
use SDPMlab\Anser\Service\Action;
use SDPMlab\Anser\Service\ServiceList;
use SDPMlab\Anser\Orchestration\Saga\SimpleSaga;
use SDPMlab\Anser\Orchestration\Saga\SimpleSagaInterface;

class TestSimpleSaga extends SimpleSaga
{
    public function testMethod1()
    {
        return "hi";
    }

    public function orderCompensation()
    {
        $orderAction = $this->getOrchestrator()->getStepAction('order');
        return $orderAction->getMeaningData();
    }
}

class TestTransStartOrch extends Orchestrator
{
    protected function definition()
    {
        $this->transStart(TestSimpleSaga::class);
    }
}

class TestSetCompensationMethod extends Orchestrator
{
    protected function definition()
    {
        $this->transStart(TestSimpleSaga::class);

        $this->setStep()
            ->addAction("order", SagaTest::$getOrder)
            ->setCompensationMethod("orderCompensation");
    }
}

class SagaTest extends CIUnitTestCase
{
    
    static string $testString = '';
    static ActionInterface $getOrder;
    static ActionInterface $getPayment
    ;
    static ActionInterface $failService;

    protected function setUp(): void
    {
        parent::setUp();
        ServiceList::cleanServiceList();
        self::$testString = '';
        self::$getOrder = (new Action("http://localhost:8080", "GET", "/api/v1/order/1"))
            ->doneHandler(function (
                ResponseInterface $response,
                Action $runtimeAction
            ) {
                $body = $response->getBody()->getContents();
                $data = json_decode($body, true);
                $runtimeAction->setMeaningData($data['data']);
            });
        self::$getPayment = (new Action("http://localhost:8081", "GET", "/api/v1/payment/1"));
        self::$failService = (new Action("http://localhost:8082", "GET", "/api/v1/fail"));
    }

    public function testTransStart()
    {
        $orch = new TestTransStartOrch();
        $orch->build();

        $saga = $orch->getSagaInstance();
        $this->assertInstanceOf(Saga::class, $saga);

        $simpleSagaInstance = $this->getPrivateProperty($saga, "simpleSagaInstance");
        $this->assertInstanceOf(SimpleSagaInterface::class, $simpleSagaInstance);
        $this->assertEquals($simpleSagaInstance->testMethod1(), "hi");
    }

    public function testSetCompensation()
    {
        $orch = new TestSetCompensationMethod();
        $orch->build();

        $saga = $orch->getSagaInstance();
        $this->assertInstanceOf(Saga::class, $saga);

        $compensationMethods = $this->getPrivateProperty($saga, 'compensationMethods');
        $this->assertEquals($compensationMethods[0], 'orderCompensation');

        $simpleSagaInstance = $this->getPrivateProperty($saga, "simpleSagaInstance");
        $this->assertEquals([
            "products_id" => [1, 2, 3, 4],
            "created_time" => "2021-04-05 10:15:55",
            "total_price" => 2156
        ], $simpleSagaInstance->orderCompensation());
    }

}
