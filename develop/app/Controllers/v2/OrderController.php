<?php

namespace App\Controllers\v2;

use CodeIgniter\API\ResponseTrait;
use App\Controllers\BaseController;
use App\Services\UserService;
use App\Models\v2\OrderModel;
use App\Entities\v2\OrderEntity;

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
     * [GET] api/v2/order/{order_key}
     * Get someone order by order key.
     *
     * @param string $orderKey
     * @return void
     */
    public function show($order_key = null)
    {
        if (is_null($order_key)) {
            return $this->fail("The Order key is required", 404);
        }

        $orderModel  = new OrderModel();
        $orderEntity = new OrderEntity();

        $orderEntity = $orderModel->where("u_key", $this->u_key)->find($order_key);

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

        $p_key   = $data["p_key"]  ?? null;
        $amount  = $data["amount"] ?? null;
        $price   = $data["price"]  ?? null;
        $u_key   = $this->u_key;

        if (is_null($p_key)|| is_null($amount)) {
            return $this->fail("Incoming data error", 404);
        }

        $now       = date("Y-m-d H:i:s");
        $order_key = sha1($u_key . $p_key . $now);

        $orderModel = new OrderModel();

        $orderEntity = $orderModel->find($order_key);
        if ($orderEntity) {
            return $this->fail("Order key repeated input, Please try it later!", 403);
        }

        $orderData  = [
            "o_key"        => $order_key,
            "u_key"        => $u_key,
            "p_key"        => $p_key,
            "amount"       => $amount,
            "price"        => $price,
            "status"       => "orderCreate",
            "created_at"   => $now,
            "updated_at"   => $now
        ];

        $orderCreatedIDOrNull = $orderModel->insert($orderData);

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
    public function update($order_key = null)
    {
        if (is_null($order_key)) {
            return $this->fail("The Order key is required", 404);
        }

        $data = $this->request->getJSON(true);

        $u_key   = $this->u_key;
        $p_key   = $data["p_key"]   ?? null;
        $amount  = $data["amount"]  ?? null;
        $price   = $data["price"]   ?? null;
        $status  = $data["status"]  ?? "orderUpdate";

        $orderModel    = new OrderModel();
        $orderEntity   = new OrderEntity();

        $orderEntity = $orderModel->find($order_key);

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

        $result = $orderModel->where('o_key', $order_key)
                             ->save($orderEntity);

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
    public function delete($order_key = null)
    {
        if (is_null($order_key)) {
            return $this->fail("The Order key is required", 400);
        }

        $orderModel  = new OrderModel();

        $orderEntity = $orderModel->find($order_key);

        if (is_null($orderEntity)) {
            return $this->fail("This order not found", 404);
        }

        $setDeleteStatus = $orderModel->where('o_key', $order_key)
                                      ->set("status", "orderDelete")
                                      ->update();
        if (!$setDeleteStatus) {
            return $this->fail("This order status change to 'DELETE' fail.", 400);
        }

        $result = $orderModel->delete($order_key);

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
