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
     * create payment transaction
     *
     * @param integer $u_key
     * @param string  $o_key
     * @param integer $total
     * @param integer $nowAmount
     * @param string  $status
     * @return integer|null
     */
    public function createPaymentTransaction(int $u_key, string $o_key, int $total, int $nowAmount, string $status): ?int
    {
        try {
            $this->db->transBegin();

            $paymentData = [
                "u_key"      => $u_key,
                "o_key"      => $o_key,
                "total"      => $total,
                "status"     => $status,
                "created_at" => date("Y-m-d H:i:s"),
                "updated_at" => date("Y-m-d H:i:s")
            ];

            $this->db->table("payment")
                     ->insert($paymentData);

            $paymentInsertKey = $this->db->insertID();

            $wallet = [
                "balance"    => $nowAmount - $total,
                "updated_at" => date("Y-m-d H:i:s")
            ];

            $this->db->table("wallet")
                     ->where("u_key", $u_key)
                     ->where("balance >=", $total)
                     ->update($wallet);

            if ($this->db->transStatus() === false || $this->db->affectedRows() == 0) {
                $this->db->transRollback();
                return null;
            } else {
                $this->db->transCommit();
                return $paymentInsertKey;
            }
        } catch (\Exception $e) {
            log_message('error', '[ERROR] {exception}', ['exception' => $e]);
            return null;
        }
    }
}
