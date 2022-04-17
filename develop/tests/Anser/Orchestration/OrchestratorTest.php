<?php

namespace SDPMlab\Anser\Service;

use CodeIgniter\Test\CIUnitTestCase;
use SDPMlab\Anser\Exception\OrchestratorException;
use SDPMlab\Anser\Orchestration\Orchestrator;
use SDPMlab\Anser\Service\Action;
use SDPMlab\Anser\Service\ServiceList;

class TestOrchestrator extends Orchestrator
{
    protected function definition()
    {
    }
}

class OrchestratorTest extends CIUnitTestCase
{

    /**
     * @var \SDPMlab\Anser\Orchestration\OrchestratorInterface
     */
    protected $orchestrator;

    protected function setUp(): void
    {
        parent::setUp();
        ServiceList::cleanServiceList();
        ServiceList::addLocalService("order_service", "localhost", 8080, false);
        ServiceList::addLocalService("payment_service", "localhost", 8081, false);
        ServiceList::addLocalService("fail_service", "localhost", 8082, false);
        ServiceList::addLocalService("user_service", "localhost", 8083, false);
        $this->orchestrator = new TestOrchestrator();
    }

    public function testSetStep()
    {
        $orderAction = new Action("order_service", "GET", "/api/v1/order/1");
        $paymentAction = new Action("payment_service", "GET", "/api/v1/payment/1");
        $userAction = new Action("user_service", "GET", "/api/v1/user");
        $this->orchestrator->setStep()
            ->addAction("order", $orderAction);
        $this->orchestrator->setStep()
            ->addAction("payment", $paymentAction);
        $this->orchestrator->setStep()
            ->addAction("user", $userAction);
        $result = $this->orchestrator->build();
        $this->assertTrue($result);
        $this->assertTrue($this->orchestrator->isSuccess());
        $this->assertEquals($orderAction->getNumnerOfDoAction(), 1);
        $this->assertEquals($paymentAction->getNumnerOfDoAction(), 1);
        $this->assertEquals($userAction->getNumnerOfDoAction(), 1);
    }

    public function testFailStep()
    {
        $orderAction = new Action("order_service", "GET", "/api/v1/order/1");
        $paymentAction = new Action("payment_service", "GET", "/api/v1/payment/1");
        $userAction = new Action("user_service", "GET", "/api/v1/user");
        $failAction = new Action("fail_service", "GET", "/api/v1/fail");
        $this->orchestrator->setStep()
            ->addAction("order", $orderAction);
        $this->orchestrator->setStep()
            ->addAction("payment", $paymentAction)
            ->addAction("fail", $failAction);
        $this->orchestrator->setStep()
            ->addAction("user", $userAction);
        $this->orchestrator->build();
        $this->assertFalse($this->orchestrator->isSuccess());
        $failActions = $this->orchestrator->getFailActions();
        $this->assertArrayHasKey("fail", $failActions);
        $this->assertEquals($failAction, $failActions["fail"]);
        $this->assertEquals($paymentAction, $this->orchestrator->getStepAction("payment"));
    }

    public function testStepException()
    {
        $orderAction = new Action("order_service", "GET", "/api/v1/order/1");
        $paymentAction = new Action("payment_service", "GET", "/api/v1/payment/1");
        $userAction = new Action("user_service", "GET", "/api/v1/user");
        $this->orchestrator->setStep()
            ->addAction("order", $orderAction);
        $this->orchestrator->setStep()
            ->addAction("payment", $paymentAction);
        $this->orchestrator->setStep()
            ->addAction("user", $userAction);

        try {
            $this->orchestrator->setStep()->addAction("user", $userAction);
        } catch (\Exception $th) {
            $this->assertInstanceOf(OrchestratorException::class, $th);
        }

        try {
            $this->orchestrator->getStepAction("sdfuehtu");
        } catch (\Exception $th) {
            $this->assertInstanceOf(OrchestratorException::class, $th);
        }
    }
}
