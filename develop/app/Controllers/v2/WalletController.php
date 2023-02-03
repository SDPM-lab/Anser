<?php

namespace App\Controllers\v2;

use CodeIgniter\API\ResponseTrait;

use App\Controllers\BaseController;
use App\Models\v2\WalletModel;
use App\Services\UserService;

class WalletController extends BaseController
{
    use ResponseTrait;

    private $u_key;

    public function __construct()
    {
        $this->u_key = UserService::getUserKey();
    }

    /**
     * [GET] /api/v2/wallet
     * Get someone wallet balance by user key.
     *
     * @return void
     */
    public function show()
    {
        $walletEntity = WalletModel::getWalletByUserID($this->u_key);
        if (is_null($walletEntity)) {
            return $this->fail("This user wallet not exist", 404);
        }

        $data = [
            "u_key"   => $walletEntity->u_key,
            "balance" => $walletEntity->balance
        ];

        return $this->respond([
            "status" => true,
            "data"   => $data,
            "msg"    => "Wallet show method successful"
        ]);
    }

    /**
     * [POST] /api/v2/wallet
     * Add wallet balance or compensate.
     *
     * @return void
     */
    public function create()
    {
        $data = $this->request->getJSON(true);
        
        $addAmount = $data["addAmount"] ?? null;
        $u_key     = $this->u_key;

        if (is_null($u_key) || is_null($addAmount)) {
            return $this->fail("Incoming data error", 400);
        }

        $walletEntity = WalletModel::getWalletByUserID($this->u_key);
        if (is_null($walletEntity)) {
            return $this->fail("This user wallet not exist", 404);
        }

        $nowBalance = $walletEntity->balance;

        $walletModel = new WalletModel();

        $balance = $nowBalance + $addAmount;

        $result = $walletModel->where("u_key", $u_key)
                              ->set("balance", $balance)
                              ->update();

        if ($result) {
            return $this->respond([
                "status" => true,
                "msg"    => "Wallet create method successful"
            ]);
        } else {
            return $this->fail("Wallet create method fail", 400);
        }
    }
}
