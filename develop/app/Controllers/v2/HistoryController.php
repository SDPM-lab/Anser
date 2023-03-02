<?php

namespace App\Controllers\v2;

use CodeIgniter\API\ResponseTrait;
use App\Controllers\BaseController;
use App\Models\v2\InventoryHistoryModel;
use App\Models\v2\OrderHistoryModel;
use App\Models\v2\PaymentHistoryModel;
use App\Models\v2\WalletHistoryModel;

class HistoryController extends BaseController
{
    use ResponseTrait;


    /**
     * [POST] Get inventory history by orch_key
     *
     * @return void
     */
    public function getInventoryHistory()
    {
        $data = $this->request->getJSON(true);

        $orch_key = $data["orch_key"] ?? null;

        if (is_null($orch_key)) {
            return $this->fail("The orchestrator key is needed.", 404);
        }

        $inventoryModel = new InventoryHistoryModel();

        $result = $inventoryModel->where('orch_key', $orch_key)
                                 ->orderBy('created_at', 'DESC')
                                 ->find();

        if ($result) {
            return $this->respond([
                "status" => true,
                "data"   => $result,
                "msg"    => "Get inventory history method successful."
            ]);
        } else {
            return $this->respond([
                "status" => true,
                "data"   => null,
                "msg"    => "Null if you wanna add a condition."
            ]);
        }
    }

    /**
     * [POST] Get order history by orch_key
     *
     * @return void
     */
    public function getOrderHistory()
    {
        $data = $this->request->getJSON(true);

        $orch_key = $data["orch_key"] ?? null;

        if (is_null($orch_key)) {
            return $this->fail("The orchestrator key is needed.", 404);
        }

        $orderHistoryModel = new OrderHistoryModel();

        $result = $orderHistoryModel->where('orch_key', $orch_key)
                                    ->orderBy('created_at', 'DESC')
                                    ->find();

        if ($result) {
            return $this->respond([
                "status" => true,
                "data"   => $result,
                "msg"    => "Get order history method successful."
            ]);
        } else {
            return $this->respond([
                "status" => true,
                "data"   => null,
                "msg"    => "Null if you wanna add a condition."
            ]);
        }
    }

    /**
     * [POST] Get payment history by orch_key
     *
     * @return void
     */
    public function getPaymentHistory()
    {
        $data = $this->request->getJSON(true);

        $orch_key = $data["orch_key"] ?? null;

        if (is_null($orch_key)) {
            return $this->fail("The orchestrator key is needed.", 404);
        }

        $paymentHistoryModel = new PaymentHistoryModel();

        $result = $paymentHistoryModel->where('orch_key', $orch_key)
                                      ->orderBy('created_at', 'DESC')
                                      ->find();

        if ($result) {
            return $this->respond([
                "status" => true,
                "data"   => $result,
                "msg"    => "Get payment history method successful."
            ]);
        } else {
            return $this->respond([
                "status" => true,
                "data"   => null,
                "msg"    => "Null if you wanna add a condition."
            ]);
        }
    }

    /**
     * [POST] Get wallet history by orch_key
     *
     * @return void
     */
    public function getWalletHistory()
    {
        $data = $this->request->getJSON(true);

        $orch_key = $data["orch_key"] ?? null;

        if (is_null($orch_key)) {
            return $this->fail("The orchestrator key is needed.", 404);
        }

        $walletHistoryModel = new WalletHistoryModel();

        $result = $walletHistoryModel->where('orch_key', $orch_key)
                                      ->orderBy('created_at', 'DESC')
                                      ->find();

        if ($result) {
            return $this->respond([
                "status" => true,
                "data"   => $result,
                "msg"    => "Get wallet history method successful."
            ]);
        } else {
            return $this->respond([
                "status" => true,
                "data"   => null,
                "msg"    => "Null if you wanna add a condition."
            ]);
        }
    }
}
