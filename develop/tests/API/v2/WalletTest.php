<?php

use Tests\Support\DatabaseTestCase;
use App\Models\v2\WalletModel;

class WalletTest extends DatabaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Extra code to run before each test
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->db->table('db_wallet')->emptyTable('db_wallet');
    }

    public function testShow()
    {
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

        //user not exist test
        $notExistUserHeaders = [
            'X-User-Key' => 999
        ];

        $failDataResults = $this->withHeaders($notExistUserHeaders)
                                ->get('api/v2/wallet');

        $failDataResults->assertStatus(404);

        $decodeFailDataResults = json_decode($failDataResults->getJSON());

        $this->assertEquals($decodeFailDataResults->message->error, "This User is not exist!");

        // user exist test
        $headers = [
            'X-User-Key' => 2
        ];

        $successDataResults = $this->withHeaders($headers)
                                   ->get('api/v2/wallet');

        $successDataResults->assertStatus(200);

        $decodeSuccessDataResults = json_decode($successDataResults->getJSON());

        $this->assertEquals($decodeSuccessDataResults->msg, "Wallet show method successful");

        $this->seeInDatabase('db_wallet', [
            'u_key'   => $decodeSuccessDataResults->data->u_key,
            'balance' => $decodeSuccessDataResults->data->balance
        ]);
    }

    public function testCreate()
    {
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

        //data miss test
        $dataExistResults = $this->post('api/v2/wallet', []);

        $dataExistResults->assertStatus(400);

        $decodeDataExistResults = json_decode($dataExistResults->getJSON());

        $decodeDataExistResultsErrMsg = $decodeDataExistResults->messages->error;

        $this->assertEquals($decodeDataExistResultsErrMsg, "Incoming data error");

        //user not exist test
        $notExistUserHeaders= [
            'X-User-Key'=> 999
        ];

        $data = [
            "addAmount" => 500
        ];

        $failDataResults = $this->withBodyFormat('json')
                                ->withHeaders($notExistUserHeaders)
                                ->post('api/v2/wallet', $data);

        $failDataResults->assertStatus(404);

        $decodeFailDataResults = json_decode($failDataResults->getJSON());

        $this->assertEquals($decodeFailDataResults->message->error, "This User is not exist!");

        //success case test

        $headers = [
            'X-User-Key'=> 2
        ];

        $successData = [
            "addAmount" => 500
        ];

        $results = $this->withBodyFormat('json')
                        ->withHeaders($headers)
                        ->post('api/v2/wallet', $successData);

        if ($results->getStatus() == 400) {
            $successDataResultsGetMsgError = json_decode($results->getJSON())->messages->error;

            $this->assertEquals($successDataResultsGetMsgError, "Wallet create method fail");
        } else {
            $successDataResultsGetMsgError = json_decode($results->getJSON())->msg;

            $this->assertEquals($successDataResultsGetMsgError, "Wallet create method successful");

            $results->assertStatus(200);

            $wallet = new WalletModel();

            $transationAfterData = $wallet->where("u_key", $headers['X-User-Key'])->first();

            $balanceChange = $transationAfterData->balance - $walletData[1]["balance"];

            $this->assertTrue($balanceChange == $successData['addAmount']);

            $checkData = [
                "u_key"   => $headers['X-User-Key'],
                "balance" => $successData['addAmount'] + $walletData[1]["balance"],
            ];

            $this->seeInDatabase('db_wallet', $checkData);
        }
    }
}
