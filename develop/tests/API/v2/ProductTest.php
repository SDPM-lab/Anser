<?php


use Tests\Support\DatabaseTestCase;
use App\Models\v2\ProductModel;

class ProductTest extends DatabaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Extra code to run before each test
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->db->table('db_product')->emptyTable('db_product');
        $this->db->query("ALTER TABLE db_product AUTO_INCREMENT = 1");
    }

    /**
     * @test
     *
     * Get all of product
     *
     * @return void
     */
    public function testIndex()
    {
        $productionData  = array(
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

        $this->db->table("db_product")->insertBatch($productionData);

        // url with parameters
        $limit = 10;
        $search = "Pants";
        $offset = 1;
        $isDesc = "ASC";

        $results = $this->get("api/v2/product?limit={$limit}&search={$search}&offset={$offset}&isDesc={$isDesc}");

        if (!$results->isOK()) {
            $results->assertStatus(404);
        }
        $results->assertStatus(200);

        $decodeResult = json_decode($results->getJSON());

        $resultStdGetList   = $decodeResult->data->list;
        $resultStdGetAmount = $decodeResult->data->dataCount;

        $productionModel = new ProductModel();

        $testQuery = $productionModel->select('p_key,name,price,amount,created_at as createdAt,updated_at as updatedAt')
                                     ->orderBy("created_at", $isDesc)
                                     ->like("name", $search);

        $testResultAmount = $testQuery->countAllResults(false);

        $testResult = $testQuery->get($limit, $offset)->getResult();

        $this->assertEquals($resultStdGetList, $testResult);
        $this->assertEquals($resultStdGetAmount, $testResultAmount);


        //url has no parameters
        $notHasParamResults = $this->get("api/v2/product");

        if (!$notHasParamResults->isOK()) {
            $results->assertStatus(404);
        }
        $notHasParamResults->assertStatus(200);

        $decodeNotHasParamResults = json_decode($notHasParamResults->getJSON());

        $notHasParamResultsStdGetList   = $decodeNotHasParamResults->data->list;
        $notHasParamResultsStdGetAmount = $decodeNotHasParamResults->data->dataCount;

        $testNotHasParamQuery = $this->db->table('product')
                                         ->select('p_key,name,amount,price,created_at as createdAt,updated_at as updatedAt');

        $testNotHasParamAmount = $testNotHasParamQuery->countAllResults(false);
        $testNotHasParamResult = $testNotHasParamQuery->get()->getResult();

        $this->assertEquals($notHasParamResultsStdGetList, $testNotHasParamResult);
        $this->assertEquals($notHasParamResultsStdGetAmount, $testNotHasParamAmount);
    }

    /**
     * @test
     *
     * get a product
     *
     * @return void
     */
    public function testShow()
    {
        $productionData  = array(
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

        $this->db->table("db_product")->insertBatch($productionData);

        //product key isn't exist , define p_key to 999
        $keyExistResults = $this->get('api/v2/product/999');
        $keyExistResults->assertStatus(404);

        //product key is exist , define p_key to 1
        $results = $this->get('api/v2/product/1');
        $results->assertStatus(200);

        $decodeHasParamResults = json_decode($results->getJSON());

        $hasParamResultsStdGetData = $decodeHasParamResults->data;

        $testHasParamQuery = $this->db->table('product')
                                      ->select('p_key,name,amount,price,created_at as createdAt,updated_at as updatedAt')
                                      ->where('p_key', 1)
                                      ->get()
                                      ->getResult();

        $this->assertEquals($hasParamResultsStdGetData, $testHasParamQuery[0]);
    }

    /**
     * @test
     *
     * create product
     *
     * @return void
     */
    public function testCreate()
    {
        //data miss
        $dataExistResults = $this->post('api/v2/product', []);
        $dataExistResults->assertStatus(400);

        $decodeDataExistResults = json_decode($dataExistResults->getJSON());

        $decodeDataExistResultsErrMsg = $decodeDataExistResults->messages->error;

        $this->assertEquals($decodeDataExistResultsErrMsg, "Incoming data error");

        //success data
        $data = [
            "name"       => "iphone 15",
            "price"      => 32000,
            "amount"     => 25,
            "created_at" => date("Y-m-d H:i:s"),
            "updated_at" => date("Y-m-d H:i:s")
        ];

        $results = $this->withBodyFormat('json')->post('api/v2/product', $data);

        if (!$results->isOK()) {
            $results->assertStatus(400);
        }

        $results->assertStatus(200);

        $productsAssertData = [
            "name"   => "iphone 15",
            "amount" => 25,
            "price"  => 32000,
        ];

        $this->seeInDatabase("db_product", $productsAssertData);
    }

    /**
     * @test
     *
     * update product
     *
     * @return void
     */
    public function testUpdate()
    {
        $productionData = [
            "name"       => "iphone 21",
            "amount"     => 10,
            "price"      => 30000,
            "created_at" => date("Y-m-d H:i:s"),
            "updated_at" => date("Y-m-d H:i:s")
        ];

        $this->db->table("db_product")->insert($productionData);
        $insertID = $this->db->insertID();

        // p_key isn't exist
        $keyNotHasData = [
            "name"  => "iphone 15",
            "price" => 30
        ];

        $keyNotHasResults = $this->withBodyFormat('json')->put('api/v2/product/999', $keyNotHasData);
        $keyNotHasResults->assertStatus(404);

        $decodeKeyNotHasResults = json_decode($keyNotHasResults->getJSON());

        $decodeKeyNotHasResultsErrMsg = $decodeKeyNotHasResults->messages->error;

        $this->assertEquals($decodeKeyNotHasResultsErrMsg, "This product not found");

        // data missing
        $otherDataExistData = [
            "created_at" => date("Y-m-d H:i:s"),
            "updated_at" => date("Y-m-d H:i:s")
        ];

        $otherDataExistResults = $this->withBodyFormat('json')->put('api/v2/product/'.$insertID, $otherDataExistData);
        $otherDataExistResults->assertStatus(404);

        $decodeDataExistResults = json_decode($otherDataExistResults->getJSON());

        $decodeDataExistResultsErrMsg = $decodeDataExistResults->messages->error;

        $this->assertEquals($decodeDataExistResultsErrMsg, "Incoming data error");


        //success test
        $data = [
            "name"  => "iphone 22",
            "price" => 40000
        ];

        $results = $this->withBodyFormat('json')->put('api/v2/product/'.$insertID, $data);
        $results->assertStatus(200);

        //check data is create
        $this->seeInDatabase("db_product", $data);
    }

    /**
     * @test
     *
     * 刪除商品
     *
     * @return void
     */

    public function testDelete()
    {
        $productionData = [
            "name"       => "iPad Air",
            "amount"     => 10,
            "price"      => 30,
            "created_at" => date("Y-m-d H:i:s"),
            "updated_at" => date("Y-m-d H:i:s")
        ];

        $this->db->table("db_product")->insert($productionData);
        $insertID = $this->db->insertID();

        //p_key isn't exist
        $keyNotExistResults = $this->delete('api/v2/product/9999');
        $keyNotExistResults->assertStatus(404);

        //success test
        $results = $this->delete('api/v2/product/'.$insertID);
        $results->assertStatus(200);

        //check data is delete
        $deleteCheckResult = $this->grabFromDatabase('db_product', 'deleted_at', ['p_key' => $insertID]);
        $this->assertTrue(!is_null($deleteCheckResult));
    }

    public function testAddInventory()
    {
        $productionData = [
            "name"       => "iPad Air",
            "amount"     => 10,
            "price"      => 30,
            "created_at" => date("Y-m-d H:i:s"),
            "updated_at" => date("Y-m-d H:i:s")
        ];

        $this->db->table("db_product")->insert($productionData);
        $insertID = $this->db->insertID();

        // Incoming data not found test
        $errorData = [
            "p_key" => $insertID
        ];

        $dataNotFoundResults = $this->withBodyFormat('json')
                                    ->post('api/v2/inventory/addInventory', $errorData);

        $dataNotFoundResults->assertStatus(404);

        $decodeDataNotFoundResults = json_decode($dataNotFoundResults->getJSON());

        $decodeDataNotFoundResultsErrMsg = $decodeDataNotFoundResults->messages->error;

        $this->assertEquals($decodeDataNotFoundResultsErrMsg, "Incoming data not found");

        // product key not found test
        $pKeyNotExistData = [
            "p_key"     => 5,
            "addAmount" => 10
        ];

        $pKeyNotFoundResults = $this->withBodyFormat('json')
                                    ->post('api/v2/inventory/addInventory', $pKeyNotExistData);

        $pKeyNotFoundResults->assertStatus(404);

        $decodePKeyNotFoundResults = json_decode($pKeyNotFoundResults->getJSON());

        $decodePKeyNotFoundResultsErrMsg = $decodePKeyNotFoundResults->messages->error;

        $this->assertEquals($decodePKeyNotFoundResultsErrMsg, "This product not found");

        // success test
        $data = [
            "p_key"     => $insertID,
            "addAmount" => 10
        ];

        $result = $this->withBodyFormat('json')
                       ->post('api/v2/inventory/addInventory', $data);

        if (!$result->isOK()) {
            $result->assertStatus(400);
        }

        $result->assertStatus(200);

        $decodeResult = json_decode($result->getJSON());

        $decodeResultMsg = $decodeResult->msg;

        $this->assertEquals($decodeResultMsg, "Product amount add method successful.");

        $checkData = [
            "p_key"  => $insertID,
            "amount" => $productionData["amount"] + 10,
            "price"  => $productionData["price"],
            "name"   => $productionData["name"],
        ];
        $this->seeInDatabase("db_product", $checkData);
    }

    public function testReduceInventory()
    {
        $productionData = [
            "name"       => "iPad Air",
            "amount"     => 10,
            "price"      => 30,
            "created_at" => date("Y-m-d H:i:s"),
            "updated_at" => date("Y-m-d H:i:s")
        ];

        $this->db->table("db_product")->insert($productionData);
        $insertID = $this->db->insertID();

        //Incoming data not found test
        $errorData = [
            "p_key" => $insertID
        ];

        $dataNotFoundResults = $this->withBodyFormat('json')
                                    ->post('api/v2/inventory/reduceInventory', $errorData);

        $dataNotFoundResults->assertStatus(404);

        $decodeDataNotFoundResults = json_decode($dataNotFoundResults->getJSON());

        $decodeDataNotFoundResultsErrMsg = $decodeDataNotFoundResults->messages->error;

        $this->assertEquals($decodeDataNotFoundResultsErrMsg, "Incoming data not found");

        // product key not found test
        $pKeyNotExistData = [
            "p_key"        => 5,
            "reduceAmount" => 10
        ];

        $pKeyNotFoundResults = $this->withBodyFormat('json')
                                    ->post('api/v2/inventory/reduceInventory', $pKeyNotExistData);

        $pKeyNotFoundResults->assertStatus(404);

        $decodePKeyNotFoundResults = json_decode($pKeyNotFoundResults->getJSON());

        $decodePKeyNotFoundResultsErrMsg = $decodePKeyNotFoundResults->messages->error;

        $this->assertEquals($decodePKeyNotFoundResultsErrMsg, "This product not found");

        //product amount not enough test
        $amountNotEnoughData = [
            "p_key"        => $insertID,
            "reduceAmount" => $productionData["amount"]+10
        ];

        $amountNotEnoughResults = $this->withBodyFormat('json')
                                       ->post('api/v2/inventory/reduceInventory', $amountNotEnoughData);

        $amountNotEnoughResults->assertStatus(400);

        $decodeAmountNotEnoughResults = json_decode($amountNotEnoughResults->getJSON());

        $decodeAmountNotEnoughResultsErrMsg = $decodeAmountNotEnoughResults->messages->error;

        $this->assertEquals($decodeAmountNotEnoughResultsErrMsg, "This product amount not enough");

        // success test
        $data = [
            "p_key"        => $insertID,
            "reduceAmount" => 10
        ];

        $result = $this->withBodyFormat('json')
                       ->post('api/v2/inventory/reduceInventory', $data);

        if (!$result->isOK()) {
            $result->assertStatus(400);
        }

        $result->assertStatus(200);

        $decodeResult = json_decode($result->getJSON());

        $decodeResultMsg = $decodeResult->msg;

        $this->assertEquals($decodeResultMsg, "Product amount reduce method successful.");

        $checkData = [
            "p_key"  => $insertID,
            "amount" => $productionData["amount"]-10,
            "price"  => $productionData["price"],
            "name"   => $productionData["name"],
        ];

        $this->seeInDatabase("db_product", $checkData);
    }
}
