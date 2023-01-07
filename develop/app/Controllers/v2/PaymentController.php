<?php

namespace App\Controllers\v2;

use CodeIgniter\API\ResponseTrait;

use App\Controllers\BaseController;
use App\Models\v2\PaymentModel;
use App\Entities\v2\PaymentEntity;
use App\Models\v2\WalletModel;
use App\Services\User;

class PaymentController extends BaseController
{
    use ResponseTrait;

    /**
     * 使用者 key 從 user service 取得
     *
     * @var int
     */
    private $u_key;

    public function __construct()
    {
        $this->u_key     = User::getUserKey();
    }

    /**
     * [GET] /api/v1/payment
     * get payment data
     *
     * @return void
     */
    public function index()
    {
        $limit = $this->request->getGet("limit") ?? 10;
        $offset = $this->request->getGet("offset") ?? 0;
        $search = $this->request->getGet("search") ?? 0;
        $isDesc = $this->request->getGet("isDesc") ?? "desc";
        $u_key = $this->u_key;

        $paymentModel = new PaymentModel();
        $paymentEntity = new PaymentEntity();

        $query = $paymentModel->orderBy("created_at", $isDesc ? "DESC" : "ASC");
        if ($search !== 0) {
            $query->like("o_key", $search);
        }
        $dataCount = $query->countAllResults(false);
        $payments = $query->where("u_key", $u_key)->findAll($limit, $offset);

        $data = [
            "list" => [],
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
            return $this->fail("payment data not found", 404);
        }

        return $this->respond([
            "status" => true,
            "data" => $data,
            "msg" => "payment index method successful",
        ]);
    }

    /**
     * [GET] /api/v1/payment/{paymentKey}
     * get payment data by payment key
     *
     * @param int $paymentKey
     * @return void
     */
    public function show($paymentKey = null)
    {
        if ($paymentKey == null) {
            return $this->fail("Incoming data(paymentKey) not true", 404);
        }

        $paymentEntity = PaymentModel::getPayment($paymentKey, $this->u_key);
        if (is_null($paymentEntity)) {
            return $this->fail("This payment is exist , please check again.", 404);
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
            "data" => $data,
            "msg" => "payment show method successful",
        ]);
    }

    /**
     * [POST] /api/v1/payment
     * create payment and reduce user balance
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
        $status = "paymentCreate";

        if (is_null($u_key) || is_null($o_key) || is_null($amount) || is_null($price)) {
            return $this->fail("Incoming data not true", 400);
        }

        $total = $amount * $price;

        $paymentModel  = new PaymentModel();
        $walletModel   = new WalletModel();

        $paymentEntity = $paymentModel::getPaymentByOrderKey($o_key, $this->u_key);
        if (!is_null($paymentEntity)) {
            return $this->fail("This payment is exist , please check again.", 400);
        }

        $userWallet = $walletModel::getWalletByUserID($u_key);
        $userBalance = $userWallet->balance;
        if (is_null($userBalance)) {
            return $this->fail("This user not exist", 400);
        }

        if ($userBalance < $total) {
            return $this->fail("Insufficient balance", 400);
        }

        $createResult = $paymentModel->createPaymentTransaction($u_key, $o_key, $total, $userBalance, $status);

        if (is_null($createResult)) {
            return $this->fail("payment fail by payment data error", 400);
        }

        return $this->respond([
            "status" => true,
            "user" => [
                "id" =>$u_key,
                "balance" => WalletModel::getWalletByUserID($u_key)->balance
            ],
            "msg" => "payment create method successful."
        ]);
    }

    /**
     * [PUT] /api/v1/payment
     * update payment price
     *
     * @param int $paymentKey
     * @return void
     */
    public function update($paymentKey = null)
    {
        $data = $this->request->getJSON(true);

        if (is_null($data["total"]) || is_null($paymentKey)) {
            return $this->fail("Incoming data not true", 400);
        }

        $total = $data["total"];
        $total = $data["status"] ?? "paymentUpdate";

        $paymentModel = new PaymentModel();
        $paymentEntity = new PaymentEntity();

        $paymentEntity = PaymentModel::getPayment($paymentKey, $this->u_key);
        if (is_null($paymentEntity)) {
            return $this->fail("This payment is exist , please check again.", 404);
        }

        $paymentEntity->total = $total;

        $result = $paymentModel->update($paymentKey, $paymentEntity->toRawArray(true));

        if ($result) {
            return $this->respond([
                "status" => true,
                "msg" => "payment update method successful."
            ]);
        } else {
            return $this->fail("payment update fail", 400);
        }
    }

    /**
     * [DELETE] /api/v1/payment/{paymentKey}
     * 刪除訂單付款資訊
     *
     * @param [type] $paymentKey
     * @return void
     */
    public function delete($paymentKey = null)
    {
        if (is_null($paymentKey)) {
            return $this->fail("Incoming data(payment key) not true", 404);
        }

        $paymentEntity = PaymentModel::getPayment($paymentKey, $this->u_key);
        if (is_null($paymentEntity)) {
            return $this->fail("This payment data us not found", 404);
        }

        $paymentModel = new PaymentModel();

        $result = $paymentModel->deletePaymentTransaction($paymentKey);

        if ($result) {
            return $this->respond([
                "status" => true,
                "msg" => "payment delete method successful."
            ]);
        } else {
            return $this->fail("payment delete fail", 400);
        }
    }
}
