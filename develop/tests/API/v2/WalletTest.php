<?php

use Tests\Support\DatabaseTestCase;
use App\Models\v2\WalletModel;

class WalletTest extends DatabaseTestCase
{
    protected $walletData;

    protected $headers;

    public function setUp(): void
    {
        parent::setUp();

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

        $this->headers = [
            'X-User-Key' => 2
        ];
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->db->table('db_wallet')->emptyTable('db_wallet');
    }

    /**
     * @test
     *
     * [FAIL CASE] Use non-existent user key to get wallet
     *
     * @return void
     */
    public function testShowWalletUKeyNotExistFail()
    {
        $this->db->table('db_wallet')->insertBatch($this->walletData);

        $notExistUserHeaders = [
            'X-User-Key' => 999
        ];

        $failDataResults = $this->withHeaders($notExistUserHeaders)
                                ->get('api/v2/wallet');

        $failDataResults->assertStatus(404);

        $decodeFailDataResults = json_decode($failDataResults->getJSON());

        $this->assertEquals($decodeFailDataResults->message->error, "This User is not exist!");
    }

    /**
     * @test
     *
     * [SUCCESS CASE] Use exist user key to get all wallet
     *
     * @return void
     */
    public function testShowWalletDataCompleteSuccess()
    {
        $this->db->table('db_wallet')->insertBatch($this->walletData);

        $successDataResults = $this->withHeaders($this->headers)
                                   ->get('api/v2/wallet');

        $successDataResults->assertStatus(200);

        $decodeSuccessDataResults = json_decode($successDataResults->getJSON());

        $this->assertEquals($decodeSuccessDataResults->msg, "Wallet show method successful");

        $this->seeInDatabase('db_wallet', [
            'u_key'   => $decodeSuccessDataResults->data->u_key,
            'balance' => $decodeSuccessDataResults->data->balance
        ]);
    }

    /**
     * @test
     *
     * [FAIL CASE] Increase wallet data but the data is missing
     *
     * @return void
     */
    public function testIncreaseWalletDataMissingFail()
    {
        $this->db->table('db_wallet')->insertBatch($this->walletData);

        $dataExistResults = $this->post('api/v2/wallet/increaseWalletBalance', []);

        $dataExistResults->assertStatus(400);

        $decodeDataExistResults = json_decode($dataExistResults->getJSON());

        $decodeDataExistResultsErrMsg = $decodeDataExistResults->messages->error;

        $this->assertEquals($decodeDataExistResultsErrMsg, "Incoming data error");
    }

    /**
     * @test
     *
     * [FAIL CASE] Use non-existent user key to increase wallet
     *
     * @return void
     */
    public function testIncreaseWalletUKeyNotExist()
    {
        $this->db->table('db_wallet')->insertBatch($this->walletData);

        $notExistUserHeaders= [
            'X-User-Key'=> 999
        ];

        $data = [
            "addAmount" => 500
        ];

        $failDataResults = $this->withBodyFormat('json')
                                ->withHeaders($notExistUserHeaders)
                                ->post('api/v2/wallet/increaseWalletBalance', $data);

        $failDataResults->assertStatus(404);

        $decodeFailDataResults = json_decode($failDataResults->getJSON());

        $this->assertEquals($decodeFailDataResults->message->error, "This User is not exist!");
    }

    /**
     * @test
     *
     * [SUCCESS CASE] increase wallet balance or compensate. data complete test.
     *
     * @return void
     */
    public function testIncreaseWalletDataCompleteSuccess()
    {
        $this->db->table('db_wallet')->insertBatch($this->walletData);

        $successData = [
            "addAmount" => 500
        ];

        $results = $this->withBodyFormat('json')
                        ->withHeaders($this->headers)
                        ->post('api/v2/wallet/increaseWalletBalance', $successData);

        $successDataResultsGetMsg = json_decode($results->getJSON())->msg;

        $this->assertEquals($successDataResultsGetMsg, "Wallet increase balance successful");

        $results->assertStatus(200);

        $wallet = new WalletModel();

        $transactionAfterData = $wallet->where("u_key", $this->headers['X-User-Key'])->first();

        $balanceChange = $transactionAfterData->balance - $this->walletData[1]["balance"];

        $this->assertTrue($balanceChange == $successData['addAmount']);

        $checkData = [
            "u_key"   => $this->headers['X-User-Key'],
            "balance" => $successData['addAmount'] + $this->walletData[1]["balance"],
        ];

        $this->seeInDatabase('db_wallet', $checkData);
    }

    /**
     * @test
     *
     * [FAIL CASE] Reduce wallet data but the data is missing
     *
     * @return void
     */
    public function testReduceWalletDataMissingFail()
    {
        $this->db->table('db_wallet')->insertBatch($this->walletData);

        $dataExistResults = $this->post('api/v2/wallet/reduceWalletBalance', []);

        $dataExistResults->assertStatus(400);

        $decodeDataExistResults = json_decode($dataExistResults->getJSON());

        $decodeDataExistResultsErrMsg = $decodeDataExistResults->messages->error;

        $this->assertEquals($decodeDataExistResultsErrMsg, "Incoming data error");
    }

    /**
     * @test
     *
     * [FAIL CASE] Use non-existent user key to increase wallet
     *
     * @return void
     */
    public function testReduceWalletUKeyNotExist()
    {
        $this->db->table('db_wallet')->insertBatch($this->walletData);

        $notExistUserHeaders= [
            'X-User-Key'=> 999
        ];

        $data = [
            "reduceAmount" => 500
        ];

        $failDataResults = $this->withBodyFormat('json')
                                ->withHeaders($notExistUserHeaders)
                                ->post('api/v2/wallet/reduceWalletBalance', $data);

        $failDataResults->assertStatus(404);

        $decodeFailDataResults = json_decode($failDataResults->getJSON());

        $this->assertEquals($decodeFailDataResults->message->error, "This User is not exist!");
    }

    /**
     * @test
     *
     * [SUCCESS CASE] increase wallet balance or compensate. data complete test.
     *
     * @return void
     */
    public function testReduceWalletDataCompleteSuccess()
    {
        $this->db->table('db_wallet')->insertBatch($this->walletData);

        $successData = [
            "reduceAmount" => 500
        ];

        $results = $this->withBodyFormat('json')
                        ->withHeaders($this->headers)
                        ->post('api/v2/wallet/reduceWalletBalance', $successData);

        $successDataResultsGetMsg = json_decode($results->getJSON())->msg;

        $this->assertEquals($successDataResultsGetMsg, "Wallet reduce balance successful");

        $results->assertStatus(200);

        $wallet = new WalletModel();

        $transactionAfterData = $wallet->where("u_key", $this->headers['X-User-Key'])->first();

        $balanceChange = $this->walletData[1]["balance"] - $transactionAfterData->balance;

        $this->assertTrue($balanceChange == $successData['reduceAmount']);

        $checkData = [
            "u_key"   => $this->headers['X-User-Key'],
            "balance" => $this->walletData[1]["balance"] - $successData['reduceAmount'],
        ];

        $this->seeInDatabase('db_wallet', $checkData);
    }
}
