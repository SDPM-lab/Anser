<?php

namespace App\Models\v2;

use CodeIgniter\Model;
use App\Entities\v2\PaymentEntity;

class PaymentModel extends Model
{
    protected $DBGroup          = 'default';
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
     * Get Payment By order key
     *
     * @param integer  $o_key
     * @param integer $u_key
     * @return PaymentEntity|null
     */
    public static function getPaymentByOrderKey(int $o_key, int $u_key): ?PaymentEntity
    {
        $paymentModel = new PaymentModel();

        $paymentEntity = $paymentModel->asObject(PaymentEntity::class)
            ->where("u_key", $u_key)
            ->where("o_key", $o_key)
            ->first();

        return $paymentEntity;
    }

    /**
     * get someone payment data
     *
     * @param integer $paymentKey
     * @param integer $userKey
     * @return PaymentEntity|null
     */
    public static function getPayment(int $paymentKey, int $userKey): ?PaymentEntity
    {
        $paymentModel = new PaymentModel();

        $paymentEntity = $paymentModel->where("u_key", $userKey)
        ->find($paymentKey);

        return $paymentEntity;
    }

    /**
     * create payment transaction
     *
     * @param integer $u_key
     * @param string $o_key
     * @param integer $total
     * @param integer $nowAmount
     * @param string $type
     * @return bool
     */
    public function createPaymentTransaction(int $u_key, string $o_key, int $total, int $nowAmount, string $status): bool
    {
        try {
            $this->db->transBegin();

            $paymentData = [
                "u_key"  => $u_key,
                "o_key"  => $o_key,
                "total"  => $total,
                "status" => $status,
                "created_at" => date("Y-m-d H:i:s"),
                "updated_at" => date("Y-m-d H:i:s")
            ];

            $this->db->table("payment")
            ->insert($paymentData);

            $wallet = [
                "balance" => $nowAmount - $total,
                "updated_at" => date("Y-m-d H:i:s")
            ];

            $this->db->table("wallet")
                ->where("u_key", $u_key)
                ->where("balance >=", $total)
                ->update($wallet);

            if ($this->db->transStatus() === false || $this->db->affectedRows() == 0) {
                $this->db->transRollback();
                return false;
            } else {
                $this->db->transCommit();
                return true;
            }
        } catch (\Exception $e) {
            log_message('error', '[ERROR] {exception}', ['exception' => $e]);
            return false;
        }
    }

    /**
     *delete payment transaction
     *
     * @param integer $pm_key
     * @return boolean
     */
    public function deletePaymentTransaction(int $pm_key): bool
    {
        try {
            $this->db->transStart();

            $time = [
                "deleted_at" => date("Y-m-d H:i:s")
            ];

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
