<?php

namespace App\Models\v2;

use CodeIgniter\Model;
use App\Entities\v2\OrderEntity;

class OrderModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'order';
    protected $primaryKey       = 'o_key';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = OrderEntity::class;
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = ['o_key', 'u_key', 'p_key', 'amount', "price", 'status'];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    /**
     * Get Order By orderID
     *
     * @param int $orderKey
     * @return OrderEntity|null
     */
    public static function getOrder(int $orderKey): ?OrderEntity
    {
        $orderModel = new OrderModel();

        $orderEntity = $orderModel->find($orderKey);

        return $orderEntity;
    }

    /**
     * Get Order By orderID And User key
     *
     * @param integer $orderKey
     * @param integer $userKey
     * @return OrderEntity|null
     */
    public static function getOrderByOrderAndUserKey(int $orderKey, int $userKey): ?OrderEntity
    {
        $orderModel = new OrderModel();

        $orderEntity = $orderModel->where('o_key', $orderKey)
                                  ->where('u_key', $userKey)
                                  ->first();

        return $orderEntity;
    }

    /**
     * Create Order
     *
     * @param integer $u_key
     * @param integer $p_key
     * @param integer $amount
     * @param integer $price (product price)
     * @return int|null
     */
    public function createOrderTransaction(int $u_key, int $p_key, int $amount, int $price): ?int
    {
        $now        = date("Y-m-d H:i:s");
        $orderData  = [
            "u_key"        => $u_key,
            "p_key"        => $p_key,
            "amount"       => $amount,
            "price"        => $price,
            "status"       => "orderCreate",
            "created_at"   => $now,
            "updated_at"   => $now
        ];

        try {
            $this->db->transStart();

            $this->db->table("order")->insert($orderData);

            $orderId = $this->db->insertID();

            $result = $this->db->transComplete();

            if ($result) {
                return $orderId;
            } else {
                return null;
            }
        } catch (\Exception $e) {
            log_message('error', '[ERROR] {exception}', ['exception' => $e]);
            return null;
        }
    }

    /**
     * Delete Order
     *
     * @param integer $orderKey
     * @return boolean|null
     */
    public function deleteOrderTransaction(int $orderKey): ?bool
    {
        try {
            $this->db->transStart();

            $time = [
                "deleted_at" => date("Y-m-d H:i:s")
            ];

            $this->db->table("order")
                ->where("o_key", $orderKey)
                ->update($time);

            $result = $this->db->transComplete();
        } catch (\Exception $e) {
            log_message('error', '[ERROR] {exception}', ['exception' => $e]);
            return false;
        }
        return $result;
    }
}
