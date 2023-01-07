<?php

namespace App\Controllers\Dtm;

use CodeIgniter\API\ResponseTrait;

use App\Controllers\BaseController;
use App\Models\v2\WalletModel;
use App\Services\User;

class WalletController extends BaseController
{
    use ResponseTrait;

    private $u_key;

    public function __construct()
    {
        $this->u_key = User::getUserKey();
    }

    /**
     * [GET] /api/v2/wallet/{userKey}
     * get someone wallet balance by user key
     *
     * @param int $userKey
     * @return void
     */
    public function show()
    {
        $walletEntity = WalletModel::getWalletByUserID($this->u_key);
        if (is_null($walletEntity)) {
            return $this->fail("This user wallet not exist", 404);
        }

        $data = [
            "u_key" => $walletEntity->u_key,
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
     * add wallet balance or compensate
     *
     * @return void
     */
    public function create()
    {
        $data = $this->request->getJSON(true);

        $addAmount = $data["addAmount"] ?? null;

        $u_key = $this->u_key;

        if (is_null($u_key) || is_null($addAmount)) {
            return $this->fail("Incoming data error", 400);
        }

        $walletEntity = WalletModel::getWalletByUserID($this->u_key);
        if (is_null($walletEntity)) {
            return $this->fail("This user wallet not exist", 404);
        }

        $balance = $walletEntity->balance;

        $walletModel = new WalletModel();
        $result = $walletModel->addBalanceTransaction($u_key, $balance, $addAmount);

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
