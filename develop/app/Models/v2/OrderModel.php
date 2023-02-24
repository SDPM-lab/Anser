<?php

namespace App\Models\v2;

use CodeIgniter\Model;
use App\Entities\v2\OrderEntity;

class OrderModel extends Model
{
    protected $DBGroup          = USE_DB_GROUP;
    protected $table            = 'order';
    protected $primaryKey       = 'o_key';
    protected $useAutoIncrement = false;
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
     * Order create transaction.
     *
     * @param string $o_key
     * @param integer $u_key
     * @param integer $p_key
     * @param integer $amount
     * @param integer $price
     * @param string $orch_key
     * @return string|null
     */
    public function orderCreateTransaction(string $o_key, int $u_key, int $p_key, int $amount, int $price, string $orch_key): ?string
    {
        $now = date("Y-m-d H:i:s");

        $order_history = [
            "type"       => "orderCreate",
            "o_key"      => $o_key,
            "orch_key"   => $orch_key,
            "created_at" => $now,
            "updated_at" => $now,
        ];

        $order_data  = [
            "o_key"        => $o_key,
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

            $this->db->table("order_history")
                     ->insert($order_history);

            $this->db->table("order")
                     ->insert($order_data);

            $result = $this->db->transComplete();

            if ($result) {
                return $o_key;
            } else {
                return null;
            }
        } catch (\Exception $e) {
            log_message('error', '[ERROR] {exception}', ['exception' => $e]);
            return null;
        }
    }

    /**
     * Order update transaction.
     *
     * @param string $o_key
     * @param integer $u_key
     * @param integer $p_key
     * @param integer $price
     * @param string $status
     * @param integer $amount
     * @param string $orch_key
     * @return boolean
     */
    public function orderUpdateTransaction(string $o_key, int $u_key, int $p_key, int $price, string $status, int $amount, string $orch_key): bool
    {
        $now = date("Y-m-d H:i:s");

        $order_history = [
            "type"       => "orderUpdate",
            "o_key"      => $o_key,
            "orch_key"   => $orch_key,
            "created_at" => $now,
            "updated_at" => $now,
        ];

        try {
            $this->db->transStart();

            $this->db->table("order_history")
                     ->insert($order_history);

            $query = $this->db->table("order");

            if (!is_null($p_key)) {
                $query->set("p_key", $p_key);
            }
            if (!is_null($amount)) {
                $query->set("amount", $amount);
            }
            if (!is_null($price)) {
                $query->set("price", $price);
            }

            $query->set("u_key", $u_key)
                  ->set("status", $status)
                  ->where('o_key', $o_key)
                  ->update();

            $result = $this->db->transComplete();

            if ($result) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            log_message('error', '[ERROR] {exception}', ['exception' => $e]);
            return false;
        }
    }

    /**
     * Order delete transaction.
     *
     * @param string $o_key
     * @param string $orch_key
     * @return void
     */
    public function orderDeleteTransaction(string $o_key, string $orch_key): bool
    {
        $now = date("Y-m-d H:i:s");

        $order_history = [
            "type"       => "orderDelete",
            "o_key"      => $o_key,
            "orch_key"   => $orch_key,
            "created_at" => $now,
            "updated_at" => $now,
        ];

        try {
            $this->db->transStart();

            $time = [
                "status"     => "orderDelete",
                "deleted_at" => date("Y-m-d H:i:s")
            ];

            $this->db->table("order_history")
                     ->insert($order_history);

            $this->db->table("order")
                     ->where("o_key", $o_key)
                     ->update($time);

            $result = $this->db->transComplete();
            return $result;
        } catch (\Exception $e) {
            log_message('error', '[ERROR] {exception}', ['exception' => $e]);
            return false;
        }
    }
}
