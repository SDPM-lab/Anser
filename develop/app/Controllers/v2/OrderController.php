<?php

namespace App\Controllers\v2;

use CodeIgniter\API\ResponseTrait;
use App\Controllers\BaseController;
use App\Services\UserService;
use App\Models\v2\OrderModel;
use App\Entities\v2\OrderEntity;
use App\Models\v2\OrderHistoryModel;

class OrderController extends BaseController
{
    use ResponseTrait;

    private $u_key;

    public function __construct()
    {
        $this->u_key = UserService::getUserKey();
    }
    /**
     * [GET] api/v2/order/
     * Get all order.
     *
     * @return void
     */
    public function index()
    {
        $limit  = $this->request->getGet("limit")  ?? 10;
        $offset = $this->request->getGet("offset") ?? 0;
        $isDesc = $this->request->getGet("isDesc") ?? "desc";

        $orderModel  = new OrderModel();
        $orderEntity = new OrderEntity();

        $query  = $orderModel->orderBy("created_at", $isDesc ? "DESC" : "ASC");
        $query->where("u_key", $this->u_key);
        $amount = $query->countAllResults(false);
        $orders = $query->findAll($limit, $offset);

        $data = [
            "list"      => [],
            "dataCount" => $amount
        ];

        if ($orders) {
            foreach ($orders as $orderEntity) {
                $orderData = [
                    "o_key"     => $orderEntity->o_key,
                    "u_key"     => $orderEntity->u_key,
                    "p_key"     => $orderEntity->p_key,
                    "amount"    => $orderEntity->amount,
                    "createdAt" => $orderEntity->createdAt,
                    "updatedAt" => $orderEntity->updatedAt
                ];
                $data["list"][] = $orderData;
            }
        } else {
            return $this->fail("Order data not found", 404);
        }

        return $this->respond([
            "status" => true,
            "data"   => $data,
            "msg"    => "Order index method successful."
        ]);
    }


    /**
     * [GET] api/v2/order/{orderKey}
     * Get someone order by order key.
     *
     * @param string $orderKey
     * @return void
     */
    public function show($orderKey = null)
    {
        if (is_null($orderKey)) {
            return $this->fail("The Order key is required", 404);
        }

        $orderModel  = new OrderModel();
        $orderEntity = new OrderEntity();

        $orderEntity = $orderModel->where("u_key", $this->u_key)->find($orderKey);

        if ($orderEntity) {
            $data = [
                "o_key"     => $orderEntity->o_key,
                "u_key"     => $orderEntity->u_key,
                "p_key"     => $orderEntity->p_key,
                "amount"    => $orderEntity->amount,
                "createdAt" => $orderEntity->createdAt,
                "updatedAt" => $orderEntity->updatedAt
            ];
        } else {
            return $this->fail("This order not found", 404);
        }

        return $this->respond([
            "status" => true,
            "data"   => $data,
            "msg"    => "Order show method successful."
        ]);
    }

    /**
     * [POST] api/v2/order/
     * Create order.
     *
     * @return void
     */
    public function create()
    {
        $data = $this->request->getJSON(true);

        $p_key    = $data["p_key"]  ?? null;
        $amount   = $data["amount"] ?? null;
        $price    = $data["price"]  ?? null;
        $u_key    = $this->u_key;
        $orch_key = $this->request->getHeaderLine("Orch-Key")??null;

        if (is_null($orch_key)) {
            return $this->fail("The orchestrator key is needed.", 404);
        }

        if (is_null($p_key)|| is_null($amount)) {
            return $this->fail("Incoming data error", 404);
        }

        $now      = date("Y-m-d H:i:s");
        $orderKey = sha1($u_key . $p_key . $now);

        $orderModel = new OrderModel();

        $orderEntity = $orderModel->find($orderKey);
        if ($orderEntity) {
            return $this->fail("Order key repeated input, Please try it later!", 403);
        }

        $orderCreatedIDOrNull = $orderModel->orderCreateTransaction($orderKey, $u_key, $p_key, $amount, $price, $orch_key);

        if ($orderCreatedIDOrNull) {
            return $this->respond([
                "status"  => true,
                "orderID" => $orderCreatedIDOrNull,
                "msg"     => "Order create method successful."
            ]);
        } else {
            return $this->fail("Order created method fail", 400);
        }
    }

    /**
     * [PUT] api/v2/order/{orderKey}
     * Update order by order key.
     *
     * @param string $orderKey
     * @return void
     */
    public function update($orderKey = null)
    {
        $data = $this->request->getJSON(true);

        $u_key    = $this->u_key;
        $p_key    = $data["p_key"]   ?? null;
        $amount   = $data["amount"]  ?? null;
        $price    = $data["price"]   ?? null;
        $status   = $data["status"]  ?? "orderUpdate";
        $orch_key = $this->request->getHeaderLine("Orch-Key") ?? null;

        if (is_null($orch_key)) {
            return $this->fail("The orchestrator key is needed.", 404);
        }

        if (is_null($orderKey) && is_null($orch_key)) {
            return $this->fail("The Order key is required", 404);
        }

        if (is_null($orderKey)) {
            $orderHistoryModel = new OrderHistoryModel();

            $orderHistoryData = $orderHistoryModel->where('orch_key', $orch_key)
                                                  ->first();
            $orderKey = $orderHistoryData->o_key;
        }

        $orderModel    = new OrderModel();
        $orderEntity   = new OrderEntity();

        $orderEntity = $orderModel->find($orderKey);

        if (is_null($orderEntity)) {
            return $this->fail("This order not found", 404);
        }

        if (is_null($p_key)) {
            return $this->fail("The product key is required", 404);
        }

        $orderEntity->u_key  = $u_key;
        $orderEntity->p_key  = $p_key;
        $orderEntity->price  = $price;
        $orderEntity->status = $status;

        if (!is_null($amount)) {
            $orderEntity->amount = $amount;
        }

        $result = $orderModel->orderUpdateTransaction($orderKey, $u_key, $p_key, $price, $status, $amount, $orch_key);

        if ($result) {
            return $this->respond([
                "status" => true,
                "msg"    => "Order update method successful."
            ]);
        } else {
            return $this->fail("Order update method fail.", 400);
        }
    }

    /**
     * [DELETE] api/v2/order/{orderKey}
     * Delete order by order key.
     *
     * @param string $orderKey
     * @return void
     */
    public function delete($orderKey = null)
    {
        $orch_key = $this->request->getHeaderLine("Orch-Key") ?? null;

        if (is_null($orch_key)) {
            return $this->fail("The orchestrator key is needed.", 404);
        }

        if (is_null($orderKey) && is_null($orch_key)) {
            return $this->fail("The Order key is required", 400);
        }

        if (is_null($orderKey)) {
            $orderHistoryModel = new OrderHistoryModel();

            $orderHistoryData = $orderHistoryModel->where('orch_key', $orch_key)
                                                  ->first();
            $orderKey = $orderHistoryData->o_key;
        }

        $orderModel  = new OrderModel();

        $orderEntity = $orderModel->find($orderKey);

        if (is_null($orderEntity)) {
            return $this->fail("This order not found", 404);
        }

        $result = $orderModel->orderDeleteTransaction($orderKey, $orch_key);

        if ($result) {
            return $this->respond([
                "status" => true,
                "data"   => $result,
                "msg"    => "Order delete method successful."
            ]);
        } else {
            return $this->fail("Order delete method fail", 400);
        }
    }
}
