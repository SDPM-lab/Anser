<?php

use App\Models\v2\PaymentModel;
use Tests\Support\DatabaseTestCase;

class PaymentTest extends DatabaseTestCase
{
    protected $productData;

    protected $walletData;

    protected $orderData;

    protected $paymentData;

    protected $singlePaymentData;
    
    protected $headers;

    public function setUp(): void
    {
        parent::setUp();

        $this->productData  = array(
            [
                "name"       => 'T-Shirt',
                "price"      => 600,
                "amount"     => 100,
                "created_at" => date("Y-m-d H:i:s"),
                "updated_at" => date("Y-m-d H:i:s")
            ],
            [
                "name"       => 'Pants',
                "price"      => 400,
                "amount"     => 50,
                "created_at" => date("Y-m-d H:i:s"),
                "updated_at" => date("Y-m-d H:i:s")
            ],
            [
                "name"       => 'Pants-XL',
                "price"      => 500,
                "amount"     => 60,
                "created_at" => date("Y-m-d H:i:s"),
                "updated_at" => date("Y-m-d H:i:s")
            ],
            [
                "name"       => 'Jacket',
                "price"      => 5000,
                "amount"     => 100,
                "created_at" => date("Y-m-d H:i:s"),
                "updated_at" => date("Y-m-d H:i:s")
            ]
        );

        $this->walletData = [
            [
                "u_key"      => 1,
                "balance"    => 0,
                "created_at" => date("Y-m-d H:i:s"),
                "updated_at" => date("Y-m-d H:i:s")
            ],
            [
                "u_key"      => 2,
                "balance"    => 5000,
                "created_at" => date("Y-m-d H:i:s"),
                "updated_at" => date("Y-m-d H:i:s")
            ],
            [
                "u_key"      => 3,
                "balance"    => 5000000,
                "created_at" => date("Y-m-d H:i:s"),
                "updated_at" => date("Y-m-d H:i:s")
            ],
        ];

        $this->orderData = [
            [
                "o_key"      => sha1($this->walletData[0]["u_key"] . 1 . date("Y-m-d H:i:s")),
                "u_key"      => $this->walletData[0]["u_key"],
                "p_key"      => 1,
                "amount"     => 10,
                "price"      => $this->productData[0]["price"],
                "status"     => "orderCreate",
                "created_at" => date("Y-m-d H:i:s"),
                "updated_at" => date("Y-m-d H:i:s")
            ],
            [
                "o_key"      => sha1($this->walletData[1]["u_key"] . 2 . date("Y-m-d H:i:s")),
                "u_key"      => $this->walletData[1]["u_key"],
                "p_key"      => 2,
                "amount"     => 10,
                "price"      => $this->productData[1]["price"],
                "status"     => "orderCreate",
                "created_at" => date("Y-m-d H:i:s"),
                "updated_at" => date("Y-m-d H:i:s")
            ]
        ];

        $this->paymentData = [
            [
                "o_key"  => $this->orderData[0]["o_key"],
                "u_key"  => $this->walletData[0]["u_key"],
                "total"  => $this->orderData[0]["amount"] * $this->orderData[0]["price"],
                "status" => "paymentCreate"
            ],
            [
                "o_key"  => $this->orderData[1]["o_key"],
                "u_key"  => $this->walletData[1]["u_key"],
                "total"  => $this->orderData[1]["amount"] * $this->orderData[1]["price"],
                "status" => "paymentCreate"
            ]
        ];

        $this->singlePaymentData = [
            "o_key"  => $this->orderData[0]["o_key"],
            "u_key"  => $this->walletData[0]["u_key"],
            "total"  => $this->orderData[0]["amount"] * $this->orderData[0]["price"],
            "status" => "paymentCreate"
        ];

        $this->headers = [
            'X-User-Key' => $this->walletData[0]["u_key"]
        ];
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->db->table('db_order')->emptyTable('db_order');
        $this->db->table('db_product')->emptyTable('db_product');
        $this->db->table('db_wallet')->emptyTable('db_wallet');
        $this->db->table('db_payment')->emptyTable('db_payment');
        $this->db->query("ALTER TABLE db_product AUTO_INCREMENT = 1");
        $this->db->query("ALTER TABLE db_payment AUTO_INCREMENT = 1");
    }

    /**
     * @test
     *
     * [SUCCESS CASE] Get all payment with parameters
     *
     * @return void
     */
    public function testIndexPaymentWithParametersSuccess()
    {
        $this->db->table("db_product")->insertBatch($this->productData);
        $this->db->table('db_wallet')->insertBatch($this->walletData);
        $this->db->table('db_order')->insertBatch($this->orderData);
        $this->db->table('db_payment')->insertBatch($this->paymentData);

        $data = [
            "limit"  => 3,
            'offset' => 0,
            'isDesc' => 'ASC',
        ];

        $results = $this->withHeaders($this->headers)
                        ->get("api/v2/payment?limit={$data['limit']}&offset={$data['offset']}&isDesc={$data['isDesc']}");

        $results->assertStatus(200);

        $decodeResult = json_decode($results->getJSON());

        $resultStdGetList   = $decodeResult->data->list;
        $resultStdGetAmount = $decodeResult->data->dataCount;
        $resultStdGetMsg    = $decodeResult->msg;

        $paymentModel = new PaymentModel();

        $testQuery = $paymentModel->select('pm_key,o_key,u_key,status,total')
                                  ->where('u_key', $this->walletData[0]["u_key"])
                                  ->orderBy("created_at", $data['isDesc']);

        $testResultAmount = $testQuery->countAllResults(false);

        $testResult = $testQuery->get($data['limit'], $data['offset'])
                                ->getResult();

        $this->assertEquals($resultStdGetList, $testResult);

        $this->assertEquals($resultStdGetAmount, $testResultAmount);

        $this->assertEquals($resultStdGetMsg, "Payment index method successful");
    }

    /**
     * @test
     *
     * [FAIL CASE] Get all payment with parameters
     *
     * @return void
     */
    public function testIndexPaymentWithParametersFail()
    {
        $this->db->table("db_product")->insertBatch($this->productData);
        $this->db->table('db_wallet')->insertBatch($this->walletData);
        $this->db->table('db_order')->insertBatch($this->orderData);

        $data = [
            "limit"  => 3,
            'offset' => 0,
            'isDesc' => 'ASC',
        ];

        $results = $this->withHeaders($this->headers)
                        ->get("api/v2/payment?limit={$data['limit']}&offset={$data['offset']}&isDesc={$data['isDesc']}");

        $results->assertStatus(404);

        $decodeResult = json_decode($results->getJSON());

        $resultStdGetErrMsg = $decodeResult->messages->error;

        $this->assertEquals($resultStdGetErrMsg, "Payment data not found");
    }

    /**
     * @test
     *
     * [SUCCESS CASE] Get all payment without parameters
     *
     * @return void
     */
    public function testIndexPaymentWithoutParametersSuccess()
    {
        $this->db->table("db_product")->insertBatch($this->productData);
        $this->db->table('db_wallet')->insertBatch($this->walletData);
        $this->db->table('db_order')->insertBatch($this->orderData);
        $this->db->table('db_payment')->insertBatch($this->paymentData);

        $notHasParamResults = $this->withHeaders($this->headers)
                                   ->get('api/v2/payment');

        $notHasParamResults->assertStatus(200);

        $decodeNotHasParamResults = json_decode($notHasParamResults->getJSON());

        $notHasParamResultsStdGetList   = $decodeNotHasParamResults->data->list;
        $notHasParamResultsStdGetAmount = $decodeNotHasParamResults->data->dataCount;
        $notHasParamResultsStdGetMsg    = $decodeNotHasParamResults->msg;

        $paymentModel = new PaymentModel();

        $testNotHasParamQuery = $paymentModel->select('pm_key,o_key,u_key,status,total')
                                             ->orderBy("created_at", "desc")
                                             ->where('u_key', $this->walletData[0]["u_key"]);

        $testNotHasParamAmount = $testNotHasParamQuery->countAllResults(false);
        $testNotHasParamResult = $testNotHasParamQuery->get()->getResult();

        $this->assertEquals($notHasParamResultsStdGetList, $testNotHasParamResult);

        $this->assertEquals($notHasParamResultsStdGetAmount, $testNotHasParamAmount);

        $this->assertEquals($notHasParamResultsStdGetMsg, "Payment index method successful");
    }

    /**
     * @test
     *
     * [FAIL CASE] Get all payment without parameters
     *
     * @return void
     */
    public function testIndexPaymentWithoutParametersFail()
    {
        $this->db->table("db_product")->insertBatch($this->productData);
        $this->db->table('db_wallet')->insertBatch($this->walletData);
        $this->db->table('db_order')->insertBatch($this->orderData);

        $notHasParamResults = $this->withHeaders($this->headers)
                                   ->get('api/v2/payment');

        $notHasParamResults->assertStatus(404);

        $decodeNotHasParamResults = json_decode($notHasParamResults->getJSON());
        $notHasParamResultsErrMsg = $decodeNotHasParamResults->messages->error;

        $this->assertEquals($notHasParamResultsErrMsg, "Payment data not found");
    }

    /**
     * @test
     *
     * [FAIL CASE] Get all payment but user isn't exist
     *
     * @return void
     */
    public function testIndexUserNotExistFail()
    {
        $notExistUserHeaders = [
            "X-User-Key" =>'4'
        ];

        $failReturnResults = $this->withHeaders($notExistUserHeaders)
                                  ->get('api/v2/payment');

        $failReturnResults->assertStatus(404);

        $failReturnResponseData = json_decode($failReturnResults->getJSON());

        $failReturnResponseDataErrorMsg = $failReturnResponseData->message->error;

        $this->assertEquals($failReturnResponseDataErrorMsg, "This User is not exist!");
    }

    /**
     * @test
     *
     * [FAIL CASE] Use non-existent payment key to get payment
     *
     * @return void
     */
    public function testShowNotExistPMKeyFail()
    {
        $this->db->table("db_product")->insertBatch($this->productData);
        $this->db->table('db_wallet')->insertBatch($this->walletData);
        $this->db->table('db_order')->insertBatch($this->orderData);
        $this->db->table('db_payment')->insert($this->singlePaymentData);

        //payment not found test
        $existProductResults = $this->withHeaders($this->headers)
                                    ->get("api/v2/payment/3");

        $existProductResults->assertStatus(404);

        $existProductResponseData = json_decode($existProductResults->getJSON());

        $existProductResponseDataErrorMsg = $existProductResponseData->messages->error;

        $this->assertEquals($existProductResponseDataErrorMsg, "This payment information is not exist or cannot found.");
    }

    /**
     * @test
     *
     * [SUCCESS CASE] Use exist payment key to get payment
     *
     * @return void
     */
    public function testShowPaymentDataCompleteSuccess()
    {
        $this->db->table("db_product")->insertBatch($this->productData);
        $this->db->table('db_wallet')->insertBatch($this->walletData);
        $this->db->table('db_order')->insertBatch($this->orderData);
        $this->db->table('db_payment')->insert($this->singlePaymentData);

        $insertID = $this->db->insertID();

        $result = $this->withHeaders($this->headers)
                       ->get("api/v2/payment/{$insertID}");

        $decodeResult = json_decode($result->getJSON());

        $resultData = $decodeResult->data;

        $paymentModel = new PaymentModel();

        $getDBdata = $paymentModel->select('pm_key,o_key,u_key,status,total')
                                  ->where('u_key', $this->walletData[0]["u_key"])
                                  ->where('pm_key', $insertID)
                                  ->get()
                                  ->getResult();

        $result->assertStatus(200);

        $this->assertEquals($resultData, $getDBdata[0]);
    }

    /**
     * @test
     *
     * [FAIL CASE] Get payment but user isn't exist
     *
     * @return void
     */
    public function testShowUserNotExistFail()
    {
        $notExistUserHeaders = [
            "X-User-Key" =>'4'
        ];

        $failReturnResults = $this->withHeaders($notExistUserHeaders)
                                  ->get('api/v2/payment');

        $failReturnResults->assertStatus(404);

        $failReturnResponseData = json_decode($failReturnResults->getJSON());

        $failReturnResponseDataErrorMsg = $failReturnResponseData->message->error;

        $this->assertEquals($failReturnResponseDataErrorMsg, "This User is not exist!");
    }

    /**
     * @test
     *
     * [FAIL CASE] Create payment data but the data is missing
     *
     * @return void
     */
    public function testCreatePaymentDataMissingFail()
    {
        $this->db->table("db_product")->insertBatch($this->productData);
        $this->db->table('db_wallet')->insertBatch($this->walletData);
        $this->db->table('db_order')->insertBatch($this->orderData);
        $this->db->table('db_payment')->insert($this->singlePaymentData);

        $missingData = [
            "o_key"  => $this->orderData[0]["o_key"],
            "amount" => $this->orderData[0]["amount"],
        ];

        $dataMissResults = $this->withBodyFormat('json')
                                ->withHeaders($this->headers)
                                ->post('api/v2/payment', $missingData);

        $dataMissResults->assertStatus(400);

        $decodeDataMissResults = json_decode($dataMissResults->getJSON());

        $dataMissResultsErrMsg = $decodeDataMissResults->messages->error;

        $this->assertEquals($dataMissResultsErrMsg, "Incoming data error");
    }

    /**
     * @test
     *
     * [FAIL CASE] Create payment data but the payment is exist
     *
     * @return void
     */
    public function testCreatePaymentExistFail()
    {
        $this->db->table("db_product")->insertBatch($this->productData);
        $this->db->table('db_wallet')->insertBatch($this->walletData);
        $this->db->table('db_order')->insertBatch($this->orderData);
        $this->db->table('db_payment')->insert($this->singlePaymentData);

        $notExistData = [
            "o_key"  => $this->orderData[0]["o_key"],
            "amount" => $this->orderData[0]["amount"],
            "price"  => $this->orderData[0]["price"],
        ];

        $notExistResults = $this->withBodyFormat('json')
                                ->withHeaders($this->headers)
                                ->post('api/v2/payment', $notExistData);

        $notExistResults->assertStatus(403);

        $decodeNotExistResults = json_decode($notExistResults->getJSON());

        $notExistResultsErrMsg = $decodeNotExistResults->messages->error;

        $this->assertEquals($notExistResultsErrMsg, "This payment information is exist.");
    }

    /**
     * @test
     *
     * [FAIL CASE] Create payment data but the balance is insufficient
     *
     * @return void
     */
    public function testCreatePaymentInsufficientBalance()
    {
        $this->db->table("db_product")->insertBatch($this->productData);
        $this->db->table('db_wallet')->insertBatch($this->walletData);
        $this->db->table('db_order')->insertBatch($this->orderData);
        $this->db->table('db_payment')->insert($this->singlePaymentData);

        $insufficientData = [
            "o_key"  => $this->orderData[1]["o_key"],
            "amount" => $this->orderData[1]["amount"],
            "price"  => $this->orderData[1]["price"],
        ];

        $insufficientResults = $this->withBodyFormat('json')
                                    ->withHeaders($this->headers)
                                    ->post('api/v2/payment', $insufficientData);

        $insufficientResults->assertStatus(400);

        $decodeInsufficientResults = json_decode($insufficientResults->getJSON());

        $insufficientResultsErrMsg = $decodeInsufficientResults->messages->error;

        $this->assertEquals($insufficientResultsErrMsg, "Insufficient balance");
    }

    /**
     * @test
     *
     * [SUCCESS CASE] Create payment and data complete
     *
     * @return void
     */
    public function testCreatePaymentDataCompleteSuccess()
    {
        $this->db->table("db_product")->insertBatch($this->productData);
        $this->db->table('db_wallet')->insertBatch($this->walletData);
        $this->db->table('db_order')->insertBatch($this->orderData);

        $successHeader = [
            'X-User-Key' => $this->walletData[2]["u_key"]
        ];

        $successData = [
            "o_key"  => $this->orderData[1]["o_key"],
            "amount" => $this->orderData[1]["amount"],
            "price"  => $this->orderData[1]["price"],
        ];

        $results = $this->withBodyFormat('json')
                        ->withHeaders($successHeader)
                        ->post('api/v2/payment', $successData);

        $results->assertStatus(200);

        $decodeResult = json_decode($results->getJSON());

        $decodeResultMsg = $decodeResult->msg;

        $this->assertEquals($decodeResultMsg, "Payment create method successful.");

        $checkData = [
            "o_key"   => $successData["o_key"],
            "u_key"   => $successHeader['X-User-Key'],
            "total"   => $successData["amount"] * $successData["price"],
            "status"  => "paymentCreate",
        ];

        $this->seeInDatabase('db_payment', $checkData);
    }

    /**
     * @test
     *
     * [FAIL CASE] Use non-existent payment key to update payment
     *
     * @return void
     */
    public function testUpdatePaymentNotExistPMKeyFail()
    {
        $this->db->table("db_product")->insertBatch($this->productData);
        $this->db->table('db_wallet')->insertBatch($this->walletData);
        $this->db->table('db_order')->insertBatch($this->orderData);
        $this->db->table('db_payment')->insert($this->singlePaymentData);

        $notExistData = [
            "total" => 5000,
        ];

        $notExistResults = $this->withBodyFormat('json')
                                ->withHeaders($this->headers)
                                ->put("api/v2/payment/9999", $notExistData);

        $notExistResults->assertStatus(404);

        $decodeNotExistResults = json_decode($notExistResults->getJSON());

        $notExistResultsErrMsg = $decodeNotExistResults->messages->error;

        $this->assertEquals($notExistResultsErrMsg, "This payment information is not exist");
    }

    public function testUpdatePaymentDataMissingFail()
    {
        $this->db->table("db_product")->insertBatch($this->productData);
        $this->db->table('db_wallet')->insertBatch($this->walletData);
        $this->db->table('db_order')->insertBatch($this->orderData);
        $this->db->table('db_payment')->insert($this->singlePaymentData);

        $insertID = $this->db->insertID();

        $missingData = [
            "status" => "test"
        ];
        $dataMissingResults = $this->withBodyFormat('json')
                                   ->withHeaders($this->headers)
                                   ->put("api/v2/payment/{$insertID}", $missingData);

        $dataMissingResults->assertStatus(400);

        $decodeDataMissingResult = json_decode($dataMissingResults->getJSON());

        $dataMissingResultErrMsg = $decodeDataMissingResult->messages->error;

        $this->assertEquals($dataMissingResultErrMsg, "Incoming data error");
    }

    public function testUpdatePaymentDataCompleteSuccess()
    {
        $this->db->table("db_product")->insertBatch($this->productData);
        $this->db->table('db_wallet')->insertBatch($this->walletData);
        $this->db->table('db_order')->insertBatch($this->orderData);
        $this->db->table('db_payment')->insert($this->singlePaymentData);

        $insertID = $this->db->insertID();

        $successData = [
            "total" => 5000,
        ];

        $results = $this->withBodyFormat('json')
                        ->withHeaders($this->headers)
                        ->put("api/v2/payment/{$insertID}", $successData);

        $results->assertStatus(200);

        $decodeResults = json_decode($results->getJSON());

        $resultsMsg = $decodeResults->msg;

        $this->assertEquals($resultsMsg, "Payment update method successful.");

        $checkData = [
            "o_key"   => $this->orderData[0]["o_key"],
            "u_key"   => $this->headers['X-User-Key'],
            "total"   => 5000,
            "status"  => "paymentUpdate",
        ];

        $this->seeInDatabase('db_payment', $checkData);
    }

    public function testDeletePaymentPMKeyNotExist()
    {
        $this->db->table("db_product")->insertBatch($this->productData);
        $this->db->table('db_wallet')->insertBatch($this->walletData);
        $this->db->table('db_order')->insertBatch($this->orderData);
        $this->db->table('db_payment')->insert($this->singlePaymentData);

        $notExistResults = $this->withBodyFormat('json')
                                ->withHeaders($this->headers)
                                ->delete("api/v2/payment/9999");

        $notExistResults->assertStatus(404);

        $decodeNotExistResults = json_decode($notExistResults->getJSON());

        $notExistResultsErrMsg = $decodeNotExistResults->messages->error;

        $this->assertEquals($notExistResultsErrMsg, "This payment information is not exist.");
    }

    public function testDeletePaymentDataCompleteSuccess()
    {
        $this->db->table("db_product")->insertBatch($this->productData);
        $this->db->table('db_wallet')->insertBatch($this->walletData);
        $this->db->table('db_order')->insertBatch($this->orderData);
        $this->db->table('db_payment')->insert($this->singlePaymentData);

        $insertID = $this->db->insertID();

        $result = $this->withBodyFormat('json')
                       ->withHeaders($this->headers)
                       ->delete("api/v2/payment/{$insertID}");

        $decodeResult = json_decode($result->getJSON());

        if (!$result->isOK()) {
            $result->assertStatus(400);

            $decodeResultErrMsg = $decodeResult->messages->error;

            $this->assertEquals($decodeResultErrMsg, "Payment delete fail");
        }

        $result->assertStatus(200);

        $resultMsg = $decodeResult->msg;

        $this->assertEquals($resultMsg, "Payment delete method successful.");

        $checkData = [
            "pm_key" => $insertID,
            "o_key"  => $this->orderData[0]["o_key"],
            "u_key"  => $this->walletData[0]["u_key"],
            "total"  => $this->orderData[0]["amount"] * $this->orderData[0]["price"],
            "status" => "paymentDelete"
        ];

        $this->seeInDatabase('db_payment', $checkData);
    }
}
