<?php

namespace SDPMlab\Anser\Service;

use CodeIgniter\Test\CIUnitTestCase;
use SDPMlab\Anser\Exception\StepException;
use SDPMlab\Anser\Exception\ActionException;
use SDPMlab\Anser\Orchestration\Orchestrator;
use SDPMlab\Anser\Orchestration\Step;
use SDPMlab\Anser\Service\Action;
use SDPMlab\Anser\Service\ServiceList;

class testStepOrchestrator extends Orchestrator
{
    protected function definition()
    {
        
    }
}

class StepTest extends CIUnitTestCase
{

    /**
     * @var \SDPMlab\Anser\Orchestration\Orchestrator
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
        $this->orchestrator = new testStepOrchestrator();
    }

    public function testNewStep()
    {
        $step = new Step($this->orchestrator, 0);
        $this->assertInstanceOf(Step::class, $step);
    }

    public function testAddAction()
    {
        $orderAction = new Action("order_service", "GET", "/api/v1/order/1");
        $paymentAction = new Action("payment_service", "GET", "/api/v1/payment/1");
        $step = new Step($this->orchestrator, 0);
        $this->assertTrue($step->aliasNonRepeat("getOrder"));
        $step->addAction("getOrder", $orderAction);
        $this->assertFalse($step->aliasNonRepeat("getOrder"));
        $this->assertEquals($orderAction, $step->getStepAction("getOrder"));
        try {
            $step->getStepAction("errod");
        } catch (\Exception $e) {
            $this->assertInstanceOf(StepException::class, $e);
        }
        $step->addAction("getPayment", $paymentAction);
        $this->assertEquals([
            "getOrder" => $orderAction,
            "getPayment" => $paymentAction,
        ], $step->getStepActionList());
    }
    
    public function testStartExcepotion()
    {
        $step = new Step($this->orchestrator, 0);
        try {
            $step->start();
        } catch (\Exception $e) {
            $this->assertInstanceOf(StepException::class, $e);
        }
    }


    public function testStartOneActionStep()
    {
        $orderAction = new Action("order_service", "GET", "/api/v1/order/1");
        $step = new Step($this->orchestrator, 0);
        $step->addAction("getOrder", $orderAction);
        $step->start();
        $this->assertTrue($step->isSuccess());
    }

    public function testCallableExcepotion()
    {
        $step = new Step($this->orchestrator, 0);
        $step->addAction("getOrder", "");
        try {
            $step->start();
        } catch (\Exception $e) {
            $this->assertInstanceOf(StepException::class, $e);
        }

        $step = new Step($this->orchestrator, 0);
        $step->addAction("getOrder", function(){});
        try {
            $step->start();
        } catch (\Throwable $e) {
            $this->assertInstanceOf(StepException::class, $e);
        }

        $step = new Step($this->orchestrator, 0);
        $step->addAction("getPayment", new Action("payment_service", "GET", "/api/v1/payment/1"));
        $step->addAction("getOrder", "");
        try {
            $step->start();
        } catch (\Throwable $e) {
            $this->assertInstanceOf(StepException::class, $e);
        }
    }

    public function testStartOneCallableStep()
    {
        $num = 2;
        $test = $this;
        $orchestrator = $this->orchestrator;

        $orderActionCallable = function (Orchestrator $runtimeOrchestrator) use ($num, $orchestrator, $test) {
            $test->assertEquals($runtimeOrchestrator, $orchestrator);
            return new Action("order_service", "GET", "/api/v1/order/{$num}");
        };

        $step = new Step($orchestrator, 0);
        $step->addAction("getOrder", $orderActionCallable);
        $step->start();
        $this->assertTrue($step->isSuccess());
    }

    public function testStartMultipleActionStep()
    {
        $orderAction = new Action("order_service", "GET", "/api/v1/order/1");
        $paymentAction = new Action("payment_service", "GET", "/api/v1/payment/1");
        $step = new Step($this->orchestrator, 0);
        $step->addAction("getOrder", $orderAction);
        $step->addAction("getPayment", $paymentAction);
        $step->start();
        $this->assertTrue($step->isSuccess());
    }

    public function testStartMultipleActionFailStep()
    {
        $orderAction = new Action("order_service", "GET", "/api/v1/order/1");
        $paymentAction = new Action("payment_service", "GET", "/api/v1/payment/1");
        $failAction = (new Action("fail_service", "GET", "/api/v1/fail"))
            ->failHandler(function(ActionException $e){
                $e->getAction()->setMeaningData(true);
            });
        
        $step = new Step($this->orchestrator, 0);
        $step->addAction("getOrder", $orderAction);
        $step->addAction("getPayment", $paymentAction);
        $step->addAction("fail",$failAction);
        $step->start();
        $this->assertFalse($step->isSuccess());

        $failActions = $step->getFailStepActionList();
        $this->assertArrayHasKey("fail",$failActions);
        $this->assertTrue($failAction->getMeaningData());
    }

    public function testStartMultipleActionCallableStep()
    {
        $orderAction = new Action("order_service", "GET", "/api/v1/order/1");
        $paymentAction = new Action("payment_service", "GET", "/api/v1/payment/1");
        $userAction = function(Orchestrator $runtimeOrchestrator){
            return new Action("user_service", "GET", "/api/v1/user");
        };

        $step = new Step($this->orchestrator, 0);
        $step->addAction("getOrder", $orderAction);
        $step->addAction("getPayment", $paymentAction);
        $step->addAction("user",$userAction);
        $step->start();
        $this->assertTrue($step->isSuccess());
    }

}
