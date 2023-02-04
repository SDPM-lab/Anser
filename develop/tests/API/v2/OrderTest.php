<?php


use Tests\Support\DatabaseTestCase;
use App\Models\v2\OrderModel;

class OrderTest extends DatabaseTestCase
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
        $this->db->query("ALTER TABLE db_product AUTO_INCREMENT = 1");
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

        $headers = [
            'X-User-key' => $walletData[0]["u_key"]
        ];

        // params test
        $data = [
            "limit"  => 3,
            'offset' => 0,
            'isDesc' => 'ASC',
        ];

        $results = $this->withHeaders($headers)
                        ->get("api/v2/order?limit={$data['limit']}&offset={$data['offset']}&isDesc={$data['isDesc']}");

        if (!$results->isOK()) {
            $results->assertStatus(404);
        }

        $results->assertStatus(200);

        $decodeResult = json_decode($results->getJSON());

        $resultStdGetList   = $decodeResult->data->list;
        $resultStdGetAmount = $decodeResult->data->dataCount;

        $orderModel = new OrderModel();

        $testQuery = $orderModel->select('o_key,u_key,p_key,amount,created_at as createdAt,updated_at as updatedAt')
                                ->where('u_key', $walletData[0]["u_key"])
                                ->orderBy("created_at", $data['isDesc']);

        $testResultAmount = $testQuery->countAllResults(false);
        $testResult = $testQuery->get($data['limit'], $data['offset'])->getResult();

        $this->assertEquals($resultStdGetList, $testResult);

        $this->assertEquals($resultStdGetAmount, $testResultAmount);

        // no params test
        $notHasParamResults = $this->withHeaders($headers)
                                   ->get('api/v2/order');

        if (!$notHasParamResults->isOK()) {
            $notHasParamResults->assertStatus(404);
        }
        $notHasParamResults->assertStatus(200);

        $decodeNotHasParamResults = json_decode($notHasParamResults->getJSON());

        $notHasParamResultsStdGetList   = $decodeNotHasParamResults->data->list;
        $notHasParamResultsStdGetAmount = $decodeNotHasParamResults->data->dataCount;
        $notHasParamResultsStdGetMsg    = $decodeNotHasParamResults->msg;

        $testNotHasParamQuery = $orderModel->select('o_key,u_key,p_key,amount,created_at as createdAt,updated_at as updatedAt')
                                           ->where('u_key', $walletData[0]["u_key"])
                                           ->orderBy("created_at", $data['isDesc']);

        $testNotHasParamAmount = $testNotHasParamQuery->countAllResults(false);
        $testNotHasParamResult = $testNotHasParamQuery->get()->getResult();

        $this->assertEquals($notHasParamResultsStdGetList, $testNotHasParamResult);

        $this->assertEquals($notHasParamResultsStdGetAmount, $testNotHasParamAmount);

        $this->assertEquals($notHasParamResultsStdGetMsg, "Order index method successful.");

        // filter test
        $notExistUserHeaders = [
            "X-User-Key" =>'4'
        ];

        $failReturnResults = $this->withHeaders($notExistUserHeaders)
                                  ->get('api/v2/order');

        $failReturnResponseData = json_decode($failReturnResults->getJSON());

        $failReturnResponseDataErrorMsg = $failReturnResponseData->message->error;

        $this->assertEquals($failReturnResponseDataErrorMsg, "This User is not exist!");

        // data not found test
        $notDataUserHeaders = [
            "X-User-Key" =>'3'
        ];

        $notDataResults = $this->withHeaders($notDataUserHeaders)
                               ->get('api/v2/order');

        $notDataResults->assertStatus(404);

        $decodeNotDataResults = json_decode($notDataResults->getJSON());
        $ResponseDataErrorMsg = $decodeNotDataResults->messages->error;

        $this->assertEquals($ResponseDataErrorMsg, "Order data not found");
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
            "o_key"      => sha1($walletData[0]["u_key"] . 1 . date("Y-m-d H:i:s")),
            "u_key"      => $walletData[0]["u_key"],
            "p_key"      => 1,
            "amount"     => 10,
            "price"      => $productData[0]["price"],
            "status"     => "orderCreate",
            "created_at" => date("Y-m-d H:i:s"),
            "updated_at" => date("Y-m-d H:i:s")
        ];

        $this->db->table('db_order')->insert($orderData);

        $headers = [
            'X-User-Key' => $walletData[0]["u_key"]
        ];

        //order not found test
        $existProductResults = $this->withHeaders($headers)
                                    ->get("api/v2/order/testOrderKey123");

        $existProductResponseData = json_decode($existProductResults->getJSON());

        $existProductResponseDataErrorMsg  = $existProductResponseData->messages->error;

        $this->assertEquals($existProductResponseDataErrorMsg, "This order not found");
        $existProductResults->assertStatus(404);

        // success test
        $result = $this->withHeaders($headers)
                       ->get("api/v2/order/{$orderData["o_key"]}");

        $decodeResult = json_decode($result->getJSON());

        $resultData = $decodeResult->data;

        $orderModel = new OrderModel();

        $getDBdata = $orderModel->select('o_key,u_key,p_key,amount,created_at as createdAt,updated_at as updatedAt')
                                ->where('u_key', $orderData["u_key"])
                                ->where('o_key', $orderData["o_key"])
                                ->get()
                                ->getResult();

        $this->assertEquals($resultData, $getDBdata[0]);

        // filter test
        $notExistUserHeaders = [
            "X-User-Key" =>'4'
        ];

        $failReturnResults = $this->withHeaders($notExistUserHeaders)
                                  ->get("api/v2/order/{$orderData["o_key"]}");

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
            "o_key"      => sha1($walletData[0]["u_key"] . 1 . date("Y-m-d H:i:s")),
            "u_key"      => $walletData[0]["u_key"],
            "p_key"      => 1,
            "amount"     => 10,
            "price"      => $productData[0]["price"],
            "status"     => "orderCreate",
            "created_at" => date("Y-m-d H:i:s"),
            "updated_at" => date("Y-m-d H:i:s")
        ];

        $this->db->table('db_order')->insert($orderData);

        $headers = [
            'X-User-Key' => $walletData[0]["u_key"]
        ];

        // data missing test
        $missingData = [
            "p_key" => 1
        ];

        $dataMissResults = $this->withBodyFormat('json')
                                ->withHeaders($headers)
                                ->post('api/v2/order', $missingData);

        $dataMissResults->assertStatus(404);

        $decodeDataMissResults = json_decode($dataMissResults->getJSON());

        $dataMissResultsErrMsg = $decodeDataMissResults->messages->error;

        $this->assertEquals($dataMissResultsErrMsg, "Incoming data error");

        //Order key repeated test
        $repeatedData = [
            "p_key"  => 1,
            "amount" => 10,
            "price"  => 600
        ];

        // send to request at the same time
        $this->withBodyFormat('json')->withHeaders($headers)->post('api/v2/order', $repeatedData);

        $repeatedDataResults = $this->withBodyFormat('json')
                                    ->withHeaders($headers)
                                    ->post('api/v2/order', $repeatedData);

        if ($repeatedDataResults->getStatus() == 403) {
            $decodeRepeatedDataResults = json_decode($repeatedDataResults->getJSON());

            $dataMissResultsErrMsg = $decodeRepeatedDataResults->messages->error;

            $this->assertEquals($dataMissResultsErrMsg, "Order key repeated input, Please try it later!");
        }

        // success test

        $data = [
            "p_key"  => 2,
            "amount" => 10,
            "price"  => 5000
        ];

        $results = $this->withBodyFormat('json')
                        ->withHeaders($headers)
                        ->post('api/v2/order', $data);

        if (!$results->isOK()) {
            $results->assertStatus(400);
        }
        $results->assertStatus(200);

        $decodeResult = json_decode($results->getJSON());

        $decodeResultMsg = $decodeResult->msg;

        $this->assertEquals($decodeResultMsg, "Order create method successful.");

        $checkData = [
            "o_key"   => $decodeResult->orderID,
            "u_key"   => $headers['X-User-Key'],
            "p_key"   => $data["p_key"],
            "amount"  => $data["amount"],
            "price"   => $data["price"],
            "status"  => "orderCreate",
        ];

        $this->seeInDatabase('db_order', $checkData);
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
            "o_key"      => sha1($walletData[0]["u_key"] . 1 . date("Y-m-d H:i:s")),
            "u_key"      => $walletData[0]["u_key"],
            "p_key"      => 1,
            "amount"     => 10,
            "price"      => $productData[0]["price"],
            "status"     => "orderCreate",
            "created_at" => date("Y-m-d H:i:s"),
            "updated_at" => date("Y-m-d H:i:s")
        ];

        $this->db->table('db_order')->insert($orderData);

        $headers = [
            'X-User-Key' => $walletData[0]["u_key"]
        ];

        //order not found test
        $orderNotExistData = [
            "o_key" => "testOrder123",
        ];

        $orderNotExistResults = $this->withBodyFormat('json')
                                     ->withHeaders($headers)
                                     ->put("api/v2/order/{$orderNotExistData["o_key"]}");

        $orderNotExistResults->assertStatus(404);

        $decodeOrderNotExistResults = json_decode($orderNotExistResults->getJSON());

        $decodeKeyNotHasResultsErrMsg = $decodeOrderNotExistResults->messages->error;

        $this->assertEquals($decodeKeyNotHasResultsErrMsg, "This order not found");

        // product key missing test
        $pKeyMissingData = [
            "amount" => 1000,
            "price"  => 600
        ];

        $pKeyMissingResults = $this->withBodyFormat('json')
                                    ->withHeaders($headers)
                                    ->put("api/v2/order/{$orderData["o_key"]}", $pKeyMissingData);

        $pKeyMissingResults->assertStatus(404);

        $decodePKeyMissingResults = json_decode($pKeyMissingResults->getJSON());

        $decodePKeyMissingResultsErrMsg = $decodePKeyMissingResults->messages->error;

        $this->assertEquals($decodePKeyMissingResultsErrMsg, "The product key is required");

        //success test
        $data = [
            "p_key"  => 1,
            "amount" => 1000,
            "price"  => 600
        ];

        $result = $this->withBodyFormat('json')
                       ->withHeaders($headers)
                       ->put("api/v2/order/{$orderData["o_key"]}", $data);

        if (!$result->isOK()) {
            $result->assertStatus(400);
        }

        $result->assertStatus(200);

        $decodeResult = json_decode($result->getJSON());

        $decodeResultMsg = $decodeResult->msg;

        $this->assertEquals($decodeResultMsg, "Order update method successful.");

        $checkData = [
            "o_key"  => $orderData["o_key"],
            "u_key"  => $headers["X-User-Key"],
            "p_key"  => 1,
            "amount" => 1000,
            "price"  => 600
        ];
        $this->seeInDatabase("db_order", $checkData);
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
            "o_key"      => sha1($walletData[0]["u_key"] . 1 . date("Y-m-d H:i:s")),
            "u_key"      => $walletData[0]["u_key"],
            "p_key"      => 1,
            "amount"     => 10,
            "price"      => $productData[0]["price"],
            "status"     => "orderCreate",
            "created_at" => date("Y-m-d H:i:s"),
            "updated_at" => date("Y-m-d H:i:s")
        ];

        $this->db->table('db_order')->insert($orderData);

        $headers = [
            'X-User-Key' => $walletData[0]["u_key"]
        ];

        //order not found test
        $orderNotExistData = [
            "o_key" => "testOrder123",
        ];

        $orderNotExistResults = $this->withBodyFormat('json')
                                     ->withHeaders($headers)
                                     ->delete("api/v2/order/{$orderNotExistData["o_key"]}");

        $orderNotExistResults->assertStatus(404);

        $decodeOrderNotExistResults = json_decode($orderNotExistResults->getJSON());

        $decodeKeyNotHasResultsErrMsg = $decodeOrderNotExistResults->messages->error;

        $this->assertEquals($decodeKeyNotHasResultsErrMsg, "This order not found");

        //success case
        $result = $this->withBodyFormat('json')
                       ->withHeaders($headers)
                       ->delete("api/v2/order/{$orderData["o_key"]}");

        if (!$result->isOK()) {
            $result->assertStatus(400);
        }

        $result->assertStatus(200);

        $decodeResult = json_decode($result->getJSON());

        $decodeResultMsg = $decodeResult->msg;

        $this->assertEquals($decodeResultMsg, "Order delete method successful.");

        //check data is delete
        $deleteCheckResult = $this->grabFromDatabase('db_order', 'deleted_at', ['o_key' => $orderData["o_key"]]);
        $this->assertTrue(!is_null($deleteCheckResult));
    }
}
