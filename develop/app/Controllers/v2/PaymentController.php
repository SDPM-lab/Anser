<?php

namespace App\Controllers\v2;

use CodeIgniter\API\ResponseTrait;

use App\Controllers\BaseController;
use App\Models\v2\PaymentModel;
use App\Entities\v2\PaymentEntity;
use App\Models\v2\WalletModel;
use App\Services\UserService;
use App\Models\v2\PaymentHistoryModel;

class PaymentController extends BaseController
{
    use ResponseTrait;

    private $u_key;

    public function __construct()
    {
        $this->u_key = UserService::getUserKey();
    }

    /**
     * [GET] /api/v1/payment
     * Get payment data.
     *
     * @return void
     */
    public function index()
    {
        $limit  = $this->request->getGet("limit")  ?? 10;
        $offset = $this->request->getGet("offset") ?? 0;
        $search = $this->request->getGet("search") ?? 0;
        $isDesc = $this->request->getGet("isDesc") ?? "desc";
        $u_key  = $this->u_key;

        $paymentModel  = new PaymentModel();
        $paymentEntity = new PaymentEntity();

        $query = $paymentModel->orderBy("created_at", $isDesc ? "DESC" : "ASC")
                              ->where("u_key", $u_key);
        if ($search !== 0) {
            $query->like("o_key", $search);
        }
        $dataCount = $query->countAllResults(false);
        $payments  = $query->findAll($limit, $offset);

        $data = [
            "list"      => [],
            "dataCount" => $dataCount
        ];

        if ($payments) {
            foreach ($payments as $paymentEntity) {
                $paymentData = [
                    "pm_key" => $paymentEntity->pm_key,
                    "u_key"  => $paymentEntity->u_key,
                    "o_key"  => $paymentEntity->o_key,
                    "status" => $paymentEntity->status,
                    "total"  => $paymentEntity->total
                ];
                $data["list"][] = $paymentData;
            }
        } else {
            return $this->fail("Payment data not found", 404);
        }

        return $this->respond([
            "status" => true,
            "data"   => $data,
            "msg"    => "Payment index method successful",
        ]);
    }

    /**
     * [GET] /api/v1/payment/{paymentKey}
     * Get payment data by payment key.
     *
     * @param int $paymentKey
     * @return void
     */
    public function show($paymentKey = null)
    {
        if ($paymentKey === null) {
            return $this->fail("The payment key is required.", 404);
        }

        $paymentModel = new PaymentModel();

        $paymentEntity = $this->getPaymentEntity($paymentKey);
        if (is_null($paymentEntity)) {
            return $this->fail("This payment information is not exist or cannot found.", 404);
        }

        $data = [
            "pm_key" => $paymentEntity->pm_key,
            "u_key"  => $paymentEntity->u_key,
            "o_key"  => $paymentEntity->o_key,
            "status" => $paymentEntity->status,
            "total"  => $paymentEntity->total
        ];

        return $this->respond([
            "status" => true,
            "data"   => $data,
            "msg"    => "Payment show method successful",
        ]);
    }

    /**
     * [POST] /api/v1/payment
     * Create payment and reduce user balance.
     *
     * @return void
     */
    public function create()
    {
        $data   = $this->request->getJSON(true);
        $u_key  = $this->u_key;
        $o_key  = $data["o_key"];
        $amount = $data["amount"] ?? null;
        $price  = $data["price"]  ?? null;

        $orch_key = $this->request->getHeaderLine("Orch-Key")??null;

        if (is_null($orch_key)) {
            return $this->fail("The orchestrator key is needed.", 404);
        }

        if (is_null($u_key) || is_null($o_key) || is_null($amount) || is_null($price)) {
            return $this->fail("Incoming data error", 400);
        }

        $total = $amount * $price;

        $paymentModel  = new PaymentModel();

        $paymentEntity = $paymentModel->where("u_key", $u_key)
                                      ->where("o_key", $o_key)
                                      ->first();
        if (!is_null($paymentEntity)) {
            return $this->fail("This payment information is exist.", 403);
        }

        $walletEntity = WalletModel::getWalletByUserID($this->u_key);
        if (is_null($walletEntity)) {
            return $this->fail("This user isn't exist.", 400);
        }

        $userBalance = $walletEntity->balance;

        if ($userBalance < $total) {
            return $this->fail("Insufficient balance", 400);
        }

        $paymentCreatedIDOrNull = $paymentModel->paymentCreateTransaction($u_key, $o_key, $total, $orch_key);

        if (is_null($paymentCreatedIDOrNull)) {
            return $this->fail("Payment created failed.", 400);
        }

        return $this->respond([
            "status"    => true,
            "paymentID" => $paymentCreatedIDOrNull,
            "msg"       => "Payment create method successful."
        ]);
    }

    /**
     * [PUT] /api/v1/payment
     * Update payment price.
     *
     * @param int $paymentKey
     * @return void
     */
    public function update($paymentKey = null)
    {
        $data = $this->request->getJSON(true);

        $total    = $data["total"] ?? null;
        $status   = $data["status"] ?? "paymentUpdate";
        $orch_key = $this->request->getHeaderLine("Orch-Key")??null;

        if (is_null($orch_key)) {
            return $this->fail("The orchestrator key is needed.", 404);
        }

        if (is_null($paymentKey) && is_null($orch_key)) {
            return $this->fail("The payment key is required.", 404);
        }

        if (is_null($paymentKey)) {
            $paymentHistoryModel = new PaymentHistoryModel();

            $paymentHistoryData = $paymentHistoryModel->where('orch_key', $orch_key)
                                                      ->first();
            $paymentKey = $paymentHistoryData->pm_key;
        }


        if (is_null($total) || is_null($paymentKey)) {
            return $this->fail("Incoming data error", 400);
        }

        $paymentModel  = new PaymentModel();

        $paymentEntity = $this->getPaymentEntity($paymentKey);
        if (is_null($paymentEntity)) {
            return $this->fail("This payment information is not exist", 404);
        }

        $result = $paymentModel->paymentUpdateTransaction($paymentKey, $total, $status, $orch_key);

        if ($result) {
            return $this->respond([
                "status" => true,
                "msg"    => "Payment update method successful."
            ]);
        } else {
            return $this->fail("Payment update method fail", 400);
        }
    }

    /**
     * [DELETE] /api/v1/payment/{paymentKey}
     * Delete payment.
     *
     * @param int $paymentKey
     * @return void
     */
    public function delete($paymentKey = null)
    {
        $orch_key = $this->request->getHeaderLine("Orch-Key")??null;

        if (is_null($orch_key)) {
            return $this->fail("The orchestrator key is needed.", 404);
        }

        if (is_null($paymentKey) && is_null($orch_key)) {
            return $this->fail("The payment key is required.", 404);
        }

        if (is_null($paymentKey)) {
            $paymentHistoryModel = new PaymentHistoryModel();

            $paymentHistoryData = $paymentHistoryModel->where('orch_key', $orch_key)
                                                      ->first();
            $paymentKey = $paymentHistoryData->pm_key;
        }

        $paymentModel  = new PaymentModel();

        $paymentEntity = $this->getPaymentEntity($paymentKey);
        if (is_null($paymentEntity)) {
            return $this->fail("This payment information is not exist.", 404);
        }

        $result = $paymentModel->paymentDeleteTransaction($paymentKey, $orch_key);

        if ($result) {
            return $this->respond([
                "status" => true,
                "msg"    => "Payment delete method successful."
            ]);
        } else {
            return $this->fail("Payment delete fail", 400);
        }
    }

    /**
     * Get payment entity by payment key
     *
     * @param int $payment_key
     * @return PaymentEntity|null
     */
    public function getPaymentEntity(int $payment_key = null): ?PaymentEntity
    {
        $paymentModel  = new PaymentModel();

        $paymentEntity = $paymentModel->where("u_key", $this->u_key)
                                      ->find($payment_key);

        return $paymentEntity;
    }
}
