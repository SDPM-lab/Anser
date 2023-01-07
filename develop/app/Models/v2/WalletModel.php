<?php

namespace App\Models\v2;

use CodeIgniter\Model;
use App\Entities\v2\WalletEntity;

class WalletModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'wallet';
    protected $primaryKey       = 'u_key';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = WalletEntity::class;
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = ['u_key', 'balance'];

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
     * get user balance by user id
     *
     * @param integer $u_key
     * @return WalletEntity|null
     */
    public static function getWalletByUserID(int $u_key): ?WalletEntity
    {
        $walletModel = new WalletModel();

        $walletEntity = $walletModel->find($u_key);

        return $walletEntity;
    }

    /**
     * Add wallet balance to add balance or compensate
     *
     * @param integer $u_key
     * @param integer $balance
     * @param integer $addAmount
     * @return boolean
     */
    public function addBalanceTransaction(int $u_key, int $balance, int $addAmount): bool
    {
        try {
            $this->db->transStart();

            $wallet = [
                "balance" => $balance + $addAmount
            ];

            $this->db->table("wallet")
            ->where("u_key", $u_key)
                ->update($wallet);

            $result = $this->db->transComplete();
            return $result;
        } catch (\Exception $e) {
            log_message('error', '[ERROR] {exception}', ['exception' => $e]);
            return false;
        }
        return true;
    }
}
