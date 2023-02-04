<?php

use App\Models\v2\PaymentModel;
use Tests\Support\DatabaseTestCase;

class PaymentTest extends DatabaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Extra code to run before each test
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

    public function testIndex()
    {
        $productData  = array(
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

        $this->db->table("db_product")->insertBatch($productData);

        $walletData = [
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

        $this->db->table('db_wallet')->insertBatch($walletData);

        $orderData = [
            [
                "o_key"      => sha1($walletData[0]["u_key"] . 1 . date("Y-m-d H:i:s")),
                "u_key"      => $walletData[0]["u_key"],
                "p_key"      => 1,
                "amount"     => 10,
                "price"      => $productData[0]["price"],
                "status"     => "orderCreate",
                "created_at" => date("Y-m-d H:i:s"),
                "updated_at" => date("Y-m-d H:i:s")
            ],
            [
                "o_key"      => sha1($walletData[1]["u_key"] . 2 . date("Y-m-d H:i:s")),
                "u_key"      => $walletData[1]["u_key"],
                "p_key"      => 2,
                "amount"     => 10,
                "price"      => $productData[1]["price"],
                "status"     => "orderCreate",
                "created_at" => date("Y-m-d H:i:s"),
                "updated_at" => date("Y-m-d H:i:s")
            ]
        ];

        $this->db->table('db_order')->insertBatch($orderData);

        $paymentData = [
            [
                "o_key"  => $orderData[0]["o_key"],
                "u_key"  => $walletData[0]["u_key"],
                "total"  => $orderData[0]["amount"] * $orderData[0]["price"],
                "status" => "paymentCreate"
            ],
            [
                "o_key"  => $orderData[1]["o_key"],
                "u_key"  => $walletData[1]["u_key"],
                "total"  => $orderData[1]["amount"] * $orderData[1]["price"],
                "status" => "paymentCreate"
            ]
        ];

        $this->db->table('db_payment')->insertBatch($paymentData);

        $headers = [
            'X-User-Key' => $walletData[0]["u_key"]
        ];


        // params test
        $data = [
            "limit"  => 3,
            'offset' => 0,
            'isDesc' => 'ASC',
        ];

        $results = $this->withHeaders($headers)
                        ->get("api/v2/payment?limit={$data['limit']}&offset={$data['offset']}&isDesc={$data['isDesc']}");

        if (!$results->isOK()) {
            $results->assertStatus(404);
        }

        $results->assertStatus(200);

        $decodeResult = json_decode($results->getJSON());

        $resultStdGetList   = $decodeResult->data->list;
        $resultStdGetAmount = $decodeResult->data->dataCount;
        $resultStdGetMsg    = $decodeResult->msg;

        $paymentModel = new PaymentModel();

        $testQuery = $paymentModel->select('pm_key,o_key,u_key,status,total')
                                  ->where('u_key', $walletData[0]["u_key"])
                                  ->orderBy("created_at", $data['isDesc']);

        $testResultAmount = $testQuery->countAllResults(false);

        $testResult = $testQuery->get($data['limit'], $data['offset'])
                                ->getResult();

        $this->assertEquals($resultStdGetList, $testResult);

        $this->assertEquals($resultStdGetAmount, $testResultAmount);

        $this->assertEquals($resultStdGetMsg, "Payment index method successful");

        // no params test
        $notHasParamResults = $this->withHeaders($headers)
                                   ->get('api/v2/payment');

        if (!$notHasParamResults->isOK()) {
            $notHasParamResults->assertStatus(404);
        }
        $notHasParamResults->assertStatus(200);

        $decodeNotHasParamResults = json_decode($notHasParamResults->getJSON());

        $notHasParamResultsStdGetList   = $decodeNotHasParamResults->data->list;
        $notHasParamResultsStdGetAmount = $decodeNotHasParamResults->data->dataCount;
        $notHasParamResultsStdGetMsg    = $decodeNotHasParamResults->msg;

        $testNotHasParamQuery = $paymentModel->select('pm_key,o_key,u_key,status,total')
                                             ->orderBy("created_at", $data['isDesc'])
                                             ->where('u_key', $walletData[0]["u_key"]);

        $testNotHasParamAmount = $testNotHasParamQuery->countAllResults(false);
        $testNotHasParamResult = $testNotHasParamQuery->get()->getResult();

        $this->assertEquals($notHasParamResultsStdGetList, $testNotHasParamResult);

        $this->assertEquals($notHasParamResultsStdGetAmount, $testNotHasParamAmount);

        $this->assertEquals($notHasParamResultsStdGetMsg, "Payment index method successful");

        // filter test
        $notExistUserHeaders = [
            "X-User-Key" =>'4'
        ];

        $failReturnResults = $this->withHeaders($notExistUserHeaders)
                                  ->get('api/v2/payment');

        $failReturnResults->assertStatus(404);

        $failReturnResponseData = json_decode($failReturnResults->getJSON());

        $failReturnResponseDataErrorMsg = $failReturnResponseData->message->error;

        $this->assertEquals($failReturnResponseDataErrorMsg, "This User is not exist!");

        // data not found test
        $notDataUserHeaders = [
            "X-User-Key" => '3'
        ];

        $notDataResults = $this->withHeaders($notDataUserHeaders)
                               ->get('api/v2/payment');

        $notDataResults->assertStatus(404);

        $decodeNotDataResults = json_decode($notDataResults->getJSON());
        $ResponseDataErrorMsg = $decodeNotDataResults->messages->error;

        $this->assertEquals($ResponseDataErrorMsg, "Payment data not found");
    }

    public function testShow()
    {
        $productData  = array(
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

        $this->db->table("db_product")->insertBatch($productData);

        $walletData = [
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

        $this->db->table('db_wallet')->insertBatch($walletData);

        $orderData = [
            [
                "o_key"      => sha1($walletData[0]["u_key"] . 1 . date("Y-m-d H:i:s")),
                "u_key"      => $walletData[0]["u_key"],
                "p_key"      => 1,
                "amount"     => 10,
                "price"      => $productData[0]["price"],
                "status"     => "orderCreate",
                "created_at" => date("Y-m-d H:i:s"),
                "updated_at" => date("Y-m-d H:i:s")
            ],
            [
                "o_key"      => sha1($walletData[1]["u_key"] . 2 . date("Y-m-d H:i:s")),
                "u_key"      => $walletData[1]["u_key"],
                "p_key"      => 2,
                "amount"     => 10,
                "price"      => $productData[1]["price"],
                "status"     => "orderCreate",
                "created_at" => date("Y-m-d H:i:s"),
                "updated_at" => date("Y-m-d H:i:s")
            ]
        ];

        $this->db->table('db_order')->insertBatch($orderData);

        $paymentData = [
            "o_key"  => $orderData[0]["o_key"],
            "u_key"  => $walletData[0]["u_key"],
            "total"  => $orderData[0]["amount"] * $orderData[0]["price"],
            "status" => "paymentCreate"
        ];

        $this->db->table('db_payment')->insert($paymentData);
        $insertID = $this->db->insertID();

        $headers = [
            'X-User-Key' => $walletData[0]["u_key"]
        ];

        //payment not found test
        $existProductResults = $this->withHeaders($headers)
                                    ->get("api/v2/payment/3");

        $existProductResults->assertStatus(404);

        $existProductResponseData = json_decode($existProductResults->getJSON());

        $existProductResponseDataErrorMsg  = $existProductResponseData->messages->error;

        $this->assertEquals($existProductResponseDataErrorMsg, "This payment information is not exist or cannot found.");

        // success test
        $result = $this->withHeaders($headers)
                       ->get("api/v2/payment/{$insertID}");

        $decodeResult = json_decode($result->getJSON());

        $resultData = $decodeResult->data;

        $paymentModel = new PaymentModel();

        $getDBdata = $paymentModel->select('pm_key,o_key,u_key,status,total')
                                  ->where('u_key', $walletData[0]["u_key"])
                                  ->where('pm_key', $insertID)
                                  ->get()
                                  ->getResult();

        $result->assertStatus(200);

        $this->assertEquals($resultData, $getDBdata[0]);

        // filter test
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

    public function testCreate()
    {
        $productData  = array(
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

        $this->db->table("db_product")->insertBatch($productData);

        $walletData = [
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

        $this->db->table('db_wallet')->insertBatch($walletData);

        $orderData = [
            [
                "o_key"      => sha1($walletData[0]["u_key"] . 1 . date("Y-m-d H:i:s")),
                "u_key"      => $walletData[0]["u_key"],
                "p_key"      => 1,
                "amount"     => 10,
                "price"      => $productData[0]["price"],
                "status"     => "orderCreate",
                "created_at" => date("Y-m-d H:i:s"),
                "updated_at" => date("Y-m-d H:i:s")
            ],
            [
                "o_key"      => sha1($walletData[1]["u_key"] . 2 . date("Y-m-d H:i:s")),
                "u_key"      => $walletData[1]["u_key"],
                "p_key"      => 2,
                "amount"     => 10,
                "price"      => $productData[1]["price"],
                "status"     => "orderCreate",
                "created_at" => date("Y-m-d H:i:s"),
                "updated_at" => date("Y-m-d H:i:s")
            ]
        ];

        $this->db->table('db_order')->insertBatch($orderData);

        $paymentData = [
            "o_key"  => $orderData[0]["o_key"],
            "u_key"  => $walletData[0]["u_key"],
            "total"  => $orderData[0]["amount"] * $orderData[0]["price"],
            "status" => "paymentCreate"
        ];

        $this->db->table('db_payment')->insert($paymentData);

        $headers = [
            'X-User-Key' => $walletData[0]["u_key"]
        ];

        // data missing
        $missingData = [
            "o_key"  => $orderData[0]["o_key"],
            "amount" => $orderData[0]["amount"],
        ];

        $dataMissResults = $this->withBodyFormat('json')
                                ->withHeaders($headers)
                                ->post('api/v2/payment', $missingData);

        $dataMissResults->assertStatus(400);

        $decodeDataMissResults = json_decode($dataMissResults->getJSON());

        $dataMissResultsErrMsg = $decodeDataMissResults->messages->error;

        $this->assertEquals($dataMissResultsErrMsg, "Incoming data error");

        // payment not exist test
        $notExistData = [
            "o_key"  => $orderData[0]["o_key"],
            "amount" => $orderData[0]["amount"],
            "price"  => $orderData[0]["price"],
        ];

        $notExistResults = $this->withBodyFormat('json')
                                ->withHeaders($headers)
                                ->post('api/v2/payment', $notExistData);

        $notExistResults->assertStatus(403);

        $decodeNotExistResults = json_decode($notExistResults->getJSON());

        $notExistResultsErrMsg = $decodeNotExistResults->messages->error;

        $this->assertEquals($notExistResultsErrMsg, "This payment information is exist.");

        // Insufficient balance test
        $insufficientData = [
            "o_key"  => $orderData[1]["o_key"],
            "amount" => $orderData[1]["amount"],
            "price"  => $orderData[1]["price"],
        ];

        $insufficientResults = $this->withBodyFormat('json')
                                    ->withHeaders($headers)
                                    ->post('api/v2/payment', $insufficientData);

        $insufficientResults->assertStatus(400);

        $decodeInsufficientResults = json_decode($insufficientResults->getJSON());

        $insufficientResultsErrMsg = $decodeInsufficientResults->messages->error;

        $this->assertEquals($insufficientResultsErrMsg, "Insufficient balance");

        // success test
        $successHeader = [
            'X-User-Key' => $walletData[2]["u_key"]
        ];

        $successData = [
            "o_key"  => $orderData[1]["o_key"],
            "amount" => $orderData[1]["amount"],
            "price"  => $orderData[1]["price"],
        ];

        $results = $this->withBodyFormat('json')
                        ->withHeaders($successHeader)
                        ->post('api/v2/payment', $successData);

        if (!$results->isOK()) {
            $results->assertStatus(400);
        }
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

    public function testUpdate()
    {
        $productData  = array(
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

        $this->db->table("db_product")->insertBatch($productData);

        $walletData = [
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

        $this->db->table('db_wallet')->insertBatch($walletData);

        $orderData = [
            [
                "o_key"      => sha1($walletData[0]["u_key"] . 1 . date("Y-m-d H:i:s")),
                "u_key"      => $walletData[0]["u_key"],
                "p_key"      => 1,
                "amount"     => 10,
                "price"      => $productData[0]["price"],
                "status"     => "orderCreate",
                "created_at" => date("Y-m-d H:i:s"),
                "updated_at" => date("Y-m-d H:i:s")
            ],
            [
                "o_key"      => sha1($walletData[1]["u_key"] . 2 . date("Y-m-d H:i:s")),
                "u_key"      => $walletData[1]["u_key"],
                "p_key"      => 2,
                "amount"     => 10,
                "price"      => $productData[1]["price"],
                "status"     => "orderCreate",
                "created_at" => date("Y-m-d H:i:s"),
                "updated_at" => date("Y-m-d H:i:s")
            ]
        ];

        $this->db->table('db_order')->insertBatch($orderData);

        $paymentData = [
            "o_key"  => $orderData[0]["o_key"],
            "u_key"  => $walletData[0]["u_key"],
            "total"  => $orderData[0]["amount"] * $orderData[0]["price"],
            "status" => "paymentCreate"
        ];

        $this->db->table('db_payment')->insert($paymentData);

        $insertID = $this->db->insertID();

        $headers = [
            'X-User-Key' => $walletData[0]["u_key"]
        ];

        // Incoming data error test
        $missingData = [
            "status" => "test"
        ];
        $dataMissingResults = $this->withBodyFormat('json')
                                   ->withHeaders($headers)
                                   ->put("api/v2/payment/{$insertID}", $missingData);

        $dataMissingResults->assertStatus(400);

        $decodeDataMissingResult = json_decode($dataMissingResults->getJSON());

        $dataMissingResultErrMsg = $decodeDataMissingResult->messages->error;

        $this->assertEquals($dataMissingResultErrMsg, "Incoming data error");

        //payment not exist
        $notExistData = [
            "total" => 5000,
        ];

        $notExistResults = $this->withBodyFormat('json')
                                ->withHeaders($headers)
                                ->put("api/v2/payment/9999", $notExistData);

        $notExistResults->assertStatus(404);

        $decodeNotExistResults = json_decode($notExistResults->getJSON());

        $notExistResultsErrMsg = $decodeNotExistResults->messages->error;

        $this->assertEquals($notExistResultsErrMsg, "This payment information is not exist");

        // success test
        $successData = [
            "total" => 5000,
        ];

        $results = $this->withBodyFormat('json')
                        ->withHeaders($headers)
                        ->put("api/v2/payment/{$insertID}", $successData);

        if (!$results->isOK()) {
            $results->assertStatus(400);
        }

        $results->assertStatus(200);

        $decodeResults = json_decode($results->getJSON());

        $resultsMsg = $decodeResults->msg;

        $this->assertEquals($resultsMsg, "Payment update method successful.");

        $checkData = [
            "o_key"   => $orderData[0]["o_key"],
            "u_key"   => $headers['X-User-Key'],
            "total"   => 5000,
            "status"  => "paymentUpdate",
        ];

        $this->seeInDatabase('db_payment', $checkData);
    }

    public function testDelete()
    {
        $productData  = array(
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

        $this->db->table("db_product")->insertBatch($productData);

        $walletData = [
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

        $this->db->table('db_wallet')->insertBatch($walletData);

        $orderData = [
            [
                "o_key"      => sha1($walletData[0]["u_key"] . 1 . date("Y-m-d H:i:s")),
                "u_key"      => $walletData[0]["u_key"],
                "p_key"      => 1,
                "amount"     => 10,
                "price"      => $productData[0]["price"],
                "status"     => "orderCreate",
                "created_at" => date("Y-m-d H:i:s"),
                "updated_at" => date("Y-m-d H:i:s")
            ],
            [
                "o_key"      => sha1($walletData[1]["u_key"] . 2 . date("Y-m-d H:i:s")),
                "u_key"      => $walletData[1]["u_key"],
                "p_key"      => 2,
                "amount"     => 10,
                "price"      => $productData[1]["price"],
                "status"     => "orderCreate",
                "created_at" => date("Y-m-d H:i:s"),
                "updated_at" => date("Y-m-d H:i:s")
            ]
        ];

        $this->db->table('db_order')->insertBatch($orderData);

        $paymentData = [
            "o_key"  => $orderData[0]["o_key"],
            "u_key"  => $walletData[0]["u_key"],
            "total"  => $orderData[0]["amount"] * $orderData[0]["price"],
            "status" => "paymentCreate"
        ];

        $this->db->table('db_payment')->insert($paymentData);

        $insertID = $this->db->insertID();

        $headers = [
            'X-User-Key' => $walletData[0]["u_key"]
        ];

        //payment not exist
        $notExistResults = $this->withBodyFormat('json')
                                ->withHeaders($headers)
                                ->delete("api/v2/payment/9999");

        $notExistResults->assertStatus(404);

        $decodeNotExistResults = json_decode($notExistResults->getJSON());

        $notExistResultsErrMsg = $decodeNotExistResults->messages->error;

        $this->assertEquals($notExistResultsErrMsg, "This payment information is not exist.");

        // success test
        $result = $this->withBodyFormat('json')
                       ->withHeaders($headers)
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
            "o_key"  => $orderData[0]["o_key"],
            "u_key"  => $walletData[0]["u_key"],
            "total"  => $orderData[0]["amount"] * $orderData[0]["price"],
            "status" => "paymentDelete"
        ];

        $this->seeInDatabase('db_payment', $checkData);
    }
}
