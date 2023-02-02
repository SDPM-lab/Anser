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
}
