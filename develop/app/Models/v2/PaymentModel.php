<?php

namespace App\Models\v2;

use CodeIgniter\Model;
use App\Entities\v2\PaymentEntity;

class PaymentModel extends Model
{
    protected $DBGroup          = USE_DB_GROUP;
    protected $table            = 'payment';
    protected $primaryKey       = 'pm_key';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = PaymentEntity::class;
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = ['pm_key','u_key','o_key','total','status'];

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
     * Create payment transaction.
     *
     * @param integer $u_key
     * @param string $o_key
     * @param integer $total
     * @param string $orch_key
     * @return void
     */
    public function paymentCreateTransaction(int $u_key, string $o_key, int $total, string $orch_key): ?string
    {
        $now = date("Y-m-d H:i:s");

        $payment_data  = [
            "u_key"        => $u_key,
            "o_key"        => $o_key,
            "status"       => "paymentCreate",
            "total"        => $total,
            "created_at"   => $now,
            "updated_at"   => $now
        ];

        try {
            $this->db->transStart();

            $this->db->table("payment")
                     ->insert($payment_data);

            $payment_key = $this->insertID();

            $payment_history = [
                "type"       => "paymentCreate",
                "pm_key"     => $payment_key,
                "orch_key"   => $orch_key,
                "created_at" => $now,
                "updated_at" => $now,
            ];
            $this->db->table("payment_history")
                     ->insert($payment_history);


            $result = $this->db->transComplete();

            if ($result) {
                return $payment_key;
            } else {
                return null;
            }
        } catch (\Exception $e) {
            log_message('error', '[ERROR] {exception}', ['exception' => $e]);
            return null;
        }
    }

    /**
     * Update payment transaction.
     *
     * @param integer $pm_key
     * @param integer $total
     * @param string $status
     * @param string $orch_key
     * @return void
     */
    public function paymentUpdateTransaction(int $pm_key, int $total, string $status, string $orch_key): bool
    {
        $now = date("Y-m-d H:i:s");

        $payment_history = [
            "type"       => "paymentUpdate",
            "pm_key"     => $pm_key,
            "orch_key"   => $orch_key,
            "created_at" => $now,
            "updated_at" => $now,
        ];

        try {
            $this->db->transStart();

            $this->db->table("payment")
                     ->where("pm_key", $pm_key)
                     ->set('total', $total)
                     ->set('status', $status)
                     ->update();

            $this->db->table("payment_history")
                     ->insert($payment_history);

            $result = $this->db->transComplete();

            return $result;
        } catch (\Exception $e) {
            log_message('error', '[ERROR] {exception}', ['exception' => $e]);
            return false;
        }
    }

    /**
     * Delete payment transaction.
     *
     * @param integer $pm_key
     * @param string $orch_key
     * @return boolean
     */
    public function paymentDeleteTransaction(int $pm_key, string $orch_key): bool
    {
        $now = date("Y-m-d H:i:s");

        $payment_history = [
            "type"       => "paymentDelete",
            "pm_key"     => $pm_key,
            "orch_key"   => $orch_key,
            "created_at" => $now,
            "updated_at" => $now,
        ];

        try {
            $this->db->transStart();

            $time = [
                "status"     => "paymentDelete",
                "deleted_at" => date("Y-m-d H:i:s")
            ];

            $this->db->table("payment_history")
                     ->insert($payment_history);

            $this->db->table("payment")
                     ->where("pm_key", $pm_key)
                     ->update($time);

            $result = $this->db->transComplete();
            return $result;
        } catch (\Exception $e) {
            log_message('error', '[ERROR] {exception}', ['exception' => $e]);
            return false;
        }
    }
}
