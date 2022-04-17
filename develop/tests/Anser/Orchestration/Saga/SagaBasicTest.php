<?php

namespace SDPMlab\Anser\Service;

use CodeIgniter\Test\CIUnitTestCase;
use Psr\Http\Message\ResponseInterface;
use SDPMlab\Anser\Exception\ActionException;
use SDPMlab\Anser\Orchestration\Orchestrator;
use SDPMlab\Anser\Service\Action;
use SDPMlab\Anser\Service\ServiceList;
use SDPMlab\Anser\Orchestration\Saga\SimpleSaga;

class SagaOrchestrator extends SimpleSaga
{
    public function orderCompensation()
    {
        $orderAction = $this->getOrchestrator()->getStepAction('order');
        $actionString = $orderAction->getMeaningData();
        SagaBasicTest::$testString .= $actionString;
    }

    public function paymentCompensation()
    {
        $paymentAction = $this->getOrchestrator()->getStepAction('payment');
        $actionString = $paymentAction->getMeaningData();
        SagaBasicTest::$testString .= $actionString;
    }

    public function failCompensation()
    {
        $failAction = $this->getOrchestrator()->getStepAction('fail');
        $actionString = $failAction->getMeaningData();
        SagaBasicTest::$testString .= $actionString;
    }
}

class SagaDefinitionBasicTest extends Orchestrator
{
    protected function definition()
    {
        $this->transStart(SagaOrchestrator::class);

        $this->setStep()
            ->addAction("order", SagaBasicTest::$getOrder)
            ->setCompensationMethod("orderCompensation");

        $this->setStep()
            ->addAction("payment", SagaBasicTest::$getPayment)
            ->setCompensationMethod("paymentCompensation");

        $this->setStep()
            ->addAction("fail", SagaBasicTest::$failService)
            ->setCompensationMethod("failCompensation");

        $this->transEnd();
    }
}

class SagaBasicTest extends CIUnitTestCase
{

    static string $testString = '';
    static ActionInterface $getOrder;
    static ActionInterface $getPayment;
    static ActionInterface $failService;
    static string $orderString = '_order';
    static string $paymentString = '_payment';
    static string $failString = '_fail';

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
                self::$testString .= self::$orderString;
                $runtimeAction->setMeaningData(self::$orderString);
            });

        self::$getPayment = (new Action("http://localhost:8081", "GET", "/api/v1/payment/1"))
            ->doneHandler(function (
                ResponseInterface $response,
                Action $runtimeAction
            ) {
                self::$testString .= self::$paymentString;
                $runtimeAction->setMeaningData(self::$paymentString);
            });

        self::$failService = (new Action("http://localhost:8082", "GET", "/api/v1/fail"))
            ->failHandler(function (ActionException $e) {
                self::$testString .= self::$failString;
                $e->getAction()->setMeaningData(self::$failString);
            });
    }

    /**
     * @group sagaBuildBasicTest
     */
    public function testSagaBuild()
    {
        $orchestrator = new SagaDefinitionBasicTest();
        $orchestrator->build();
        $expectedString = self::$orderString . self::$paymentString . self::$failString . self::$failString . self::$paymentString . self::$orderString;
        $this->assertEquals($expectedString, self::$testString);
    }
}
