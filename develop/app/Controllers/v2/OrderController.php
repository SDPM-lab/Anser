<?php

namespace App\Controllers\v2;

use CodeIgniter\API\ResponseTrait;
use App\Controllers\BaseController;
use App\Services\User;
use App\Models\v2\OrderModel;
use App\Entities\v2\OrderEntity;

class OrderController extends BaseController
{
    use ResponseTrait;

    private $u_key;

    public function __construct()
    {
        $this->u_key = User::getUserKey();
    }
    /**
     * [GET] api/v2/order/
     * index method
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
            "list"   => [],
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
            "data"  => $data,
            "msg" => "Order index method successful."
        ]);
    }


    /**
     * [GET] api/v2/order/{orderID}
     *  get someone order by orderID
     *
     * @param integer $orderID
     * @return void
     */
    public function show($orderID = null)
    {
        if (is_null($orderID)) {
            return $this->fail("Incoming data(Order Key) not true", 404);
        }

        $orderModel  = new OrderModel();
        $orderEntity = new OrderEntity();

        $orderEntity = $orderModel->where("u_key", $this->u_key)->find($orderID);

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
            "data" => $data,
            "msg" => "Order show method successful."
        ]);
    }

    /**
     * [POST] api/v2/order/
     * create order
     *
     * @return void
     */
    public function create()
    {
        $data = $this->request->getJSON(true);

        $p_key   = $data["p_key"]  ?? null;
        $amount  = $data["amount"] ?? null;
        $price   = $data["price"]  ?? null;
        $u_key   = $this->u_key;

        if (is_null($p_key)|| is_null($amount)) {
            return $this->fail("Incoming data error", 404);
        }

        $orderModel   = new OrderModel();

        $orderCreatedIDOrNull = $orderModel->createOrderTransaction($u_key, $p_key, $amount, $price);

        if ($orderCreatedIDOrNull) {
            return $this->respond([
                "status" => true,
                "orderID" => $orderCreatedIDOrNull,
                "msg" => "Order create method successful."
            ]);
        } else {
            return $this->fail("Order created method fail", 400);
        }
    }

    /**
     * [PUT] api/v2/order/{orderID}
     *
     * @param integer $orderID
     * @return void
     */
    public function update($orderID = null)
    {
        if (is_null($orderID)) {
            return $this->fail("Incoming data(Order Key) not true", 404);
        }

        $data = $this->request->getJSON(true);

        $u_key   = $this->u_key;
        $p_key   = $data["p_key"]   ?? null;
        $amount  = $data["amount"]  ?? null;
        $price   = $data["price"]   ?? null;
        $status  = $data["status"]  ?? "orderUpdate";

        $orderModel    = new OrderModel();
        $orderEntity   = new OrderEntity();

        $orderEntity = $orderModel->find($orderID);

        if (is_null($orderEntity)) {
            return $this->fail("This order not found", 404);
        }

        if (is_null($p_key)) {
            return $this->fail("Incoming data(Product Key) not true", 404);
        }

        $orderEntity->o_key  = $orderID;
        $orderEntity->u_key  = $u_key;
        $orderEntity->p_key  = $p_key;
        $orderEntity->price  = $price;
        $orderEntity->status = $status;

        if (!is_null($amount)) {
            $orderEntity->amount = $amount;
        }

        $result = $orderModel->where('o_key', $orderEntity->o_key)
                             ->save($orderEntity);

        if ($result) {
            return $this->respond([
                "status" => true,
                "msg" => "Order method successful."
            ]);
        } else {
            return $this->fail("Order update method fail.", 400);
        }
    }

    /**
     * [DELETE] api/v2/order/{orderID}
     * delete order
     *
     * @param integer $orderID
     * @return void
     */
    public function delete($orderID = null)
    {
        if (is_null($orderID)) {
            return $this->fail("Incoming data(Order Key) not true", 400);
        }

        $orderModel = new OrderModel();

        $result = $orderModel->deleteOrderTransaction($orderID);

        if ($result) {
            return $this->respond([
                "status" => true,
                "id" => $orderID,
                "msg" => "Order delete method successful."
            ]);
        } else {
            return $this->fail("Order delete method fail", 400);
        }
    }
}
