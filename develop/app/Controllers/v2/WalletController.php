<?php

namespace App\Controllers\v2;

use CodeIgniter\API\ResponseTrait;

use App\Controllers\BaseController;
use App\Models\v2\WalletModel;
use App\Services\UserService;
use App\Models\v2\WalletHistoryModel;

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
     * [POST] /api/v2/wallet/increaseWalletBalance
     * Increase wallet balance.
     *
     * @return void
     */
    public function increaseWalletBalance()
    {
        $data = $this->request->getJSON(true);

        $addAmount = $data["addAmount"] ?? null;
        $u_key     = $this->u_key;
        $orch_key  = $this->request->getHeaderLine("Orch-Key") ?? null;

        if (is_null($orch_key)) {
            return $this->fail("The orchestrator key is needed.", 404);
        }

        if (is_null($addAmount)) {
            return $this->fail("Incoming data error", 400);
        }

        if (is_null($u_key) && is_null($orch_key)) {
            return $this->fail("The user key is required.", 400);
        }

        if (is_null($u_key)) {
            $walletHistoryModel = new WalletHistoryModel();

            $walletHistoryData = $walletHistoryModel->where('orch_key', $orch_key)
                                                    ->first();
            $u_key = $walletHistoryData->u_key;
        }

        $walletEntity = WalletModel::getWalletByUserID($u_key);

        if (is_null($walletEntity)) {
            return $this->fail("This user wallet not exist", 404);
        }

        $nowBalance = $walletEntity->balance;

        $walletModel = new WalletModel();

        $result = $walletModel->increaseBalanceTransaction($u_key, $nowBalance, $addAmount, $orch_key);

        if ($result) {
            return $this->respond([
                "status" => true,
                "msg"    => "Wallet increase balance successful"
            ]);
        } else {
            return $this->fail("Wallet increase balance fail", 400);
        }
    }

    /**
     * [POST] /api/v2/wallet/reduceWalletBalance
     * Reduce wallet balance.
     *
     * @return void
     */
    public function reduceWalletBalance()
    {
        $data = $this->request->getJSON(true);

        $reduceAmount = $data["reduceAmount"] ?? null;
        $u_key        = $this->u_key;
        $orch_key     = $this->request->getHeaderLine("Orch-Key") ?? null;

        if (is_null($orch_key)) {
            return $this->fail("The orchestrator key is needed.", 404);
        }

        if (is_null($reduceAmount)) {
            return $this->fail("Incoming data error", 400);
        }

        if (is_null($u_key) && is_null($orch_key)) {
            return $this->fail("The user key is required.", 400);
        }

        if (is_null($u_key)) {
            $walletHistoryModel = new WalletHistoryModel();

            $walletHistoryData = $walletHistoryModel->where('orch_key', $orch_key)
                                                    ->first();
            $u_key = $walletHistoryData->u_key;
        }

        $walletEntity = WalletModel::getWalletByUserID($u_key);

        if (is_null($walletEntity)) {
            return $this->fail("This user wallet not exist", 404);
        }

        $nowBalance = $walletEntity->balance;

        if ($nowBalance < $reduceAmount) {
            return $this->fail("The user wallet balance is not enough to pay it.", 400);
        }

        $walletModel = new WalletModel();

        $result = $walletModel->reduceBalanceTransaction($u_key, $nowBalance, $reduceAmount, $orch_key);

        if ($result) {
            return $this->respond([
                "status" => true,
                "msg"    => "Wallet reduce balance successful"
            ]);
        } else {
            return $this->fail("Wallet reduce balance fail", 400);
        }
    }
}
