<?php


use Tests\Support\DatabaseTestCase;
use App\Models\v2\OrderModel;

class OrderTest extends DatabaseTestCase
{
    protected $productData;

    protected $walletData;

    protected $orderData;

    protected $singleOrderData;

    protected $headers;

    public function setUp(): void
    {
        parent::setUp();

        $this->productData = array(
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

        $this->singleOrderData = [
            "o_key"      => sha1($this->walletData[0]["u_key"] . 1 . date("Y-m-d H:i:s")),
            "u_key"      => $this->walletData[0]["u_key"],
            "p_key"      => 1,
            "amount"     => 10,
            "price"      => $this->productData[0]["price"],
            "status"     => "orderCreate",
            "created_at" => date("Y-m-d H:i:s"),
            "updated_at" => date("Y-m-d H:i:s")
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
        $this->db->query("ALTER TABLE db_product AUTO_INCREMENT = 1");
    }

    /**
     * @test
     *
     * [SUCCESS CASE] Get all orders with parameters
     *
     * @return void
     */
    public function testIndexOrderWithParametersSuccess()
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
                        ->get("api/v2/order?limit={$data['limit']}&offset={$data['offset']}&isDesc={$data['isDesc']}");

        $results->assertStatus(200);

        $decodeResult = json_decode($results->getJSON());

        $resultStdGetList   = $decodeResult->data->list;
        $resultStdGetAmount = $decodeResult->data->dataCount;

        $orderModel = new OrderModel();

        $testQuery = $orderModel->select('o_key,u_key,p_key,amount,created_at as createdAt,updated_at as updatedAt')
                                ->where('u_key', $this->walletData[0]["u_key"])
                                ->orderBy("created_at", $data['isDesc']);

        $testResultAmount = $testQuery->countAllResults(false);
        $testResult = $testQuery->get($data['limit'], $data['offset'])->getResult();

        $this->assertEquals($resultStdGetList, $testResult);

        $this->assertEquals($resultStdGetAmount, $testResultAmount);
    }

    /**
     * @test
     *
     * [FAIL CASE] Get all orders with parameters
     *
     * @return void
     */
    public function testIndexOrderWithParametersFail()
    {
        $this->db->table('db_wallet')->insertBatch($this->walletData);

        $data = [
            "limit"  => 3,
            'offset' => 0,
            'isDesc' => 'ASC',
        ];

        $results = $this->withHeaders($this->headers)
                        ->get("api/v2/order?limit={$data['limit']}&offset={$data['offset']}&isDesc={$data['isDesc']}");

        $results->assertStatus(404);

        $decodeResult = json_decode($results->getJSON());

        $decodeResultErrMsg = $decodeResult->messages->error;

        $this->assertEquals($decodeResultErrMsg, "Order data not found");
    }

    /**
     * @test
     *
     * [SUCCESS CASE] Get all order without parameters
     *
     * @return void
     */
    public function testIndexOrderWithoutParametersSuccess()
    {
        $this->db->table("db_product")->insertBatch($this->productData);
        $this->db->table('db_wallet')->insertBatch($this->walletData);
        $this->db->table('db_order')->insertBatch($this->orderData);

        $notHasParamResults = $this->withHeaders($this->headers)
                                   ->get('api/v2/order');

        $notHasParamResults->assertStatus(200);

        $decodeNotHasParamResults = json_decode($notHasParamResults->getJSON());

        $notHasParamResultsStdGetList   = $decodeNotHasParamResults->data->list;
        $notHasParamResultsStdGetAmount = $decodeNotHasParamResults->data->dataCount;
        $notHasParamResultsStdGetMsg    = $decodeNotHasParamResults->msg;

        $orderModel = new OrderModel();

        $testNotHasParamQuery = $orderModel->select('o_key,u_key,p_key,amount,created_at as createdAt,updated_at as updatedAt')
                                           ->where('u_key', $this->walletData[0]["u_key"])
                                           ->orderBy("created_at", "desc");

        $testNotHasParamAmount = $testNotHasParamQuery->countAllResults(false);
        $testNotHasParamResult = $testNotHasParamQuery->get()->getResult();

        $this->assertEquals($notHasParamResultsStdGetList, $testNotHasParamResult);

        $this->assertEquals($notHasParamResultsStdGetAmount, $testNotHasParamAmount);

        $this->assertEquals($notHasParamResultsStdGetMsg, "Order index method successful.");
    }

    /**
     * @test
     *
     * [FAIL CASE] Get all order without parameters
     *
     * @return void
     */
    public function testIndexOrderWithoutParametersFail()
    {
        $this->db->table('db_wallet')->insertBatch($this->walletData);

        $notHasParamResults = $this->withHeaders($this->headers)
                                   ->get('api/v2/order');

        $notHasParamResults->assertStatus(404);

        $decodeNotHasParamResults = json_decode($notHasParamResults->getJSON());

        $notHasParamResultsErrMsg = $decodeNotHasParamResults->messages->error;

        $this->assertEquals($notHasParamResultsErrMsg, "Order data not found");
    }

    /**
     * @test
     *
     * [FAIL CASE] Get all order but user isn't exist
     *
     * @return void
     */
    public function testIndexUserNotExistFail()
    {
        $notExistUserHeaders = [
            "X-User-Key" =>'4'
        ];

        $failReturnResults = $this->withHeaders($notExistUserHeaders)
                                  ->get('api/v2/order');

        $failReturnResponseData = json_decode($failReturnResults->getJSON());

        $failReturnResponseDataErrorMsg = $failReturnResponseData->message->error;

        $this->assertEquals($failReturnResponseDataErrorMsg, "This User is not exist!");
    }

    /**
     * @test
     *
     * [FAIL CASE] Use non-existent order key to get order
     *
     * @return void
     */
    public function testShowNotExistOKeyFail()
    {
        $this->db->table("db_product")->insertBatch($this->productData);
        $this->db->table('db_wallet')->insertBatch($this->walletData);
        $this->db->table('db_order')->insert($this->singleOrderData);

        $existProductResults = $this->withHeaders($this->headers)
                                    ->get("api/v2/order/testOrderKey123");

        $existProductResponseData = json_decode($existProductResults->getJSON());

        $existProductResponseDataErrorMsg  = $existProductResponseData->messages->error;

        $this->assertEquals($existProductResponseDataErrorMsg, "This order not found");
        $existProductResults->assertStatus(404);
    }

    /**
     * @test
     *
     * [SUCCESS CASE] Use exist order key to get order
     *
     * @return void
     */
    public function testShowDataCompleteSuccess()
    {
        $this->db->table("db_product")->insertBatch($this->productData);
        $this->db->table('db_wallet')->insertBatch($this->walletData);
        $this->db->table('db_order')->insert($this->singleOrderData);

        $result = $this->withHeaders($this->headers)
                       ->get("api/v2/order/{$this->singleOrderData["o_key"]}");

        $decodeResult = json_decode($result->getJSON());

        $resultData = $decodeResult->data;

        $orderModel = new OrderModel();

        $getDBdata = $orderModel->select('o_key,u_key,p_key,amount,created_at as createdAt,updated_at as updatedAt')
                                ->where('u_key', $this->singleOrderData["u_key"])
                                ->where('o_key', $this->singleOrderData["o_key"])
                                ->get()
                                ->getResult();

        $this->assertEquals($resultData, $getDBdata[0]);
    }

    /**
     * @test
     *
     * [FAIL CASE] Get order but user isn't exist
     *
     * @return void
     */
    public function testShowUserNotExistFail()
    {
        $existProductResults = $this->withHeaders($this->headers)
                                    ->get("api/v2/order/testOrderKey123");

        $existProductResponseData = json_decode($existProductResults->getJSON());

        $existProductResponseDataErrorMsg = $existProductResponseData->message->error;

        $this->assertEquals($existProductResponseDataErrorMsg, "This User is not exist!");

        $existProductResults->assertStatus(404);
    }

    /**
     * @test
     *
     * [FAIL CASE] Create order data but the data is missing
     *
     * @return void
     */
    public function testCreateDataMissingFail()
    {
        $this->db->table("db_product")->insertBatch($this->productData);
        $this->db->table('db_wallet')->insertBatch($this->walletData);
        $this->db->table('db_order')->insert($this->singleOrderData);

        $missingData = [
            "p_key" => 1
        ];

        $dataMissResults = $this->withBodyFormat('json')
                                ->withHeaders($this->headers)
                                ->post('api/v2/order', $missingData);

        $dataMissResults->assertStatus(404);

        $decodeDataMissResults = json_decode($dataMissResults->getJSON());

        $dataMissResultsErrMsg = $decodeDataMissResults->messages->error;

        $this->assertEquals($dataMissResultsErrMsg, "Incoming data error");
    }

    /**
     * @test
     *
     * [SUCCESS CASE] Create order and data complete
     *
     * @return void
     */
    public function testCreateDataCompleteSuccess()
    {
        $this->db->table("db_product")->insertBatch($this->productData);
        $this->db->table('db_wallet')->insertBatch($this->walletData);
        $this->db->table('db_order')->insert($this->singleOrderData);

        $data = [
            "p_key"  => 2,
            "amount" => 10,
            "price"  => 5000
        ];

        $results = $this->withBodyFormat('json')
                        ->withHeaders($this->headers)
                        ->post('api/v2/order', $data);

        $results->assertStatus(200);

        $decodeResult = json_decode($results->getJSON());

        $decodeResultMsg = $decodeResult->msg;

        $this->assertEquals($decodeResultMsg, "Order create method successful.");

        $checkData = [
            "o_key"  => $decodeResult->orderID,
            "u_key"  => $this->headers['X-User-Key'],
            "p_key"  => $data["p_key"],
            "amount" => $data["amount"],
            "price"  => $data["price"],
            "status" => "orderCreate",
        ];

        $this->seeInDatabase('db_order', $checkData);
    }

    /**
     * @test
     *
     * [FAIL CASE] Use non-existent order key to update order
     *
     * @return void
     */
    public function testUpdateOrderNotExistOKeyFail()
    {
        $this->db->table("db_product")->insertBatch($this->productData);
        $this->db->table('db_wallet')->insertBatch($this->walletData);
        $this->db->table('db_order')->insert($this->singleOrderData);

        $orderNotExistData = [
            "o_key" => "testOrder123",
        ];

        $orderNotExistResults = $this->withBodyFormat('json')
                                     ->withHeaders($this->headers)
                                     ->put("api/v2/order/{$orderNotExistData["o_key"]}");

        $orderNotExistResults->assertStatus(404);

        $decodeOrderNotExistResults = json_decode($orderNotExistResults->getJSON());

        $decodeKeyNotHasResultsErrMsg = $decodeOrderNotExistResults->messages->error;

        $this->assertEquals($decodeKeyNotHasResultsErrMsg, "This order not found");
    }

    /**
     * @test
     *
     * [FAIL CASE] Update order data but product key missing
     *
     * @return void
     */
    public function testUpdateOrderPKeyMissingFail()
    {
        $this->db->table("db_product")->insertBatch($this->productData);
        $this->db->table('db_wallet')->insertBatch($this->walletData);
        $this->db->table('db_order')->insert($this->singleOrderData);

        $pKeyMissingData = [
            "amount" => 1000,
            "price"  => 600
        ];

        $pKeyMissingResults = $this->withBodyFormat('json')
                                    ->withHeaders($this->headers)
                                    ->put("api/v2/order/{$this->singleOrderData["o_key"]}", $pKeyMissingData);

        $pKeyMissingResults->assertStatus(404);

        $decodePKeyMissingResults = json_decode($pKeyMissingResults->getJSON());

        $decodePKeyMissingResultsErrMsg = $decodePKeyMissingResults->messages->error;

        $this->assertEquals($decodePKeyMissingResultsErrMsg, "The product key is required");
    }

    /**
     * @test
     *
     * [SUCCESS CASE] Update order and data complete
     *
     * @return void
     */
    public function testUpdateOrderDataCompleteSuccess()
    {
        $this->db->table("db_product")->insertBatch($this->productData);
        $this->db->table('db_wallet')->insertBatch($this->walletData);
        $this->db->table('db_order')->insert($this->singleOrderData);

        $data = [
            "p_key"  => 1,
            "amount" => 1000,
            "price"  => 600
        ];

        $result = $this->withBodyFormat('json')
                       ->withHeaders($this->headers)
                       ->put("api/v2/order/{$this->singleOrderData["o_key"]}", $data);

        $result->assertStatus(200);

        $decodeResult = json_decode($result->getJSON());

        $decodeResultMsg = $decodeResult->msg;

        $this->assertEquals($decodeResultMsg, "Order update method successful.");

        $checkData = [
            "o_key"  => $this->singleOrderData["o_key"],
            "u_key"  => $this->headers["X-User-Key"],
            "p_key"  => 1,
            "amount" => 1000,
            "price"  => 600
        ];
        $this->seeInDatabase("db_order", $checkData);
    }

    /**
     * @test
     *
     * [FAIL CASE] Use non-existent order key to delete order
     *
     * @return void
     */
    public function testDeleteOrderOKeyNotExist()
    {
        $this->db->table("db_product")->insertBatch($this->productData);
        $this->db->table('db_wallet')->insertBatch($this->walletData);
        $this->db->table('db_order')->insert($this->singleOrderData);

        $orderNotExistData = [
            "o_key" => "testOrder123",
        ];

        $orderNotExistResults = $this->withBodyFormat('json')
                                     ->withHeaders($this->headers)
                                     ->delete("api/v2/order/{$orderNotExistData["o_key"]}");

        $orderNotExistResults->assertStatus(404);

        $decodeOrderNotExistResults = json_decode($orderNotExistResults->getJSON());

        $decodeKeyNotHasResultsErrMsg = $decodeOrderNotExistResults->messages->error;

        $this->assertEquals($decodeKeyNotHasResultsErrMsg, "This order not found");
    }

    /**
     * @test
     *
     * [SUCCESS CASE] Delete order and data complete
     *
     * @return void
     */
    public function testDeleteOrderDataCompleteSuccess()
    {
        $this->db->table("db_product")->insertBatch($this->productData);
        $this->db->table('db_wallet')->insertBatch($this->walletData);
        $this->db->table('db_order')->insert($this->singleOrderData);

        $result = $this->withBodyFormat('json')
                       ->withHeaders($this->headers)
                       ->delete("api/v2/order/{$this->singleOrderData["o_key"]}");

        $result->assertStatus(200);

        $decodeResult = json_decode($result->getJSON());

        $decodeResultMsg = $decodeResult->msg;

        $this->assertEquals($decodeResultMsg, "Order delete method successful.");

        //check data is delete
        $deleteCheckResult = $this->grabFromDatabase('db_order', 'deleted_at', ['o_key' => $this->singleOrderData["o_key"]]);
        $this->assertTrue(!is_null($deleteCheckResult));
    }
}
