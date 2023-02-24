<?php

namespace App\Models\v2;

use CodeIgniter\Model;
use App\Entities\v2\WalletEntity;

class WalletModel extends Model
{
    protected $DBGroup          = USE_DB_GROUP;
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
     * Get user balance by user id.
     *
     * @param integer $u_key
     * @return WalletEntity|null
     */
    public static function getWalletByUserID(int $u_key): ?WalletEntity
    {
        $walletModel  = new WalletModel();

        $walletEntity = $walletModel->find($u_key);

        return $walletEntity;
    }

    /**
     * Increase user wallet balance Transaction.
     *
     * @param integer $u_key
     * @param integer $balance
     * @param string $orch_key
     * @return boolean
     */
    public function increaseBalanceTransaction(int $u_key, int $nowBalance, int $addAmount, string $orch_key): bool
    {
        $now = date("Y-m-d H:i:s");

        $wallet_history = [
            "type"       => "increaseWalletBalance",
            "u_key"      => $u_key,
            "balance"    => $addAmount,
            "orch_key"   => $orch_key,
            "created_at" => $now,
            "updated_at" => $now,
        ];

        try {
            $this->db->transStart();

            $this->db->table("wallet")
                     ->where("u_key", $u_key)
                     ->set("balance", $nowBalance + $addAmount)
                     ->update();

            $this->db->table("wallet_history")
                     ->insert($wallet_history);

            $result = $this->db->transComplete();

            return $result;
        } catch (\Exception $e) {
            log_message('error', '[ERROR] {exception}', ['exception' => $e]);
            return false;
        }
    }

    /**
     * Reduce user wallet balance Transaction.
     *
     * @param integer $u_key
     * @param integer $nowBalance
     * @param integer $reduceAmount
     * @param string $orch_key
     * @return void
     */
    public function reduceBalanceTransaction(int $u_key, int $nowBalance, int $reduceAmount, string $orch_key)
    {
        $now = date("Y-m-d H:i:s");

        $wallet_history = [
            "type"       => "reduceWalletBalance",
            "u_key"      => $u_key,
            "balance"    => $reduceAmount,
            "orch_key"   => $orch_key,
            "created_at" => $now,
            "updated_at" => $now,
        ];

        try {
            $this->db->transStart();

            $this->db->table("wallet")
                     ->where("u_key", $u_key)
                     ->set("balance", $nowBalance - $reduceAmount)
                     ->update();

            $this->db->table("wallet_history")
                     ->insert($wallet_history);

            $result = $this->db->transComplete();

            return $result;
        } catch (\Exception $e) {
            log_message('error', '[ERROR] {exception}', ['exception' => $e]);
            return false;
        }
    }
}
