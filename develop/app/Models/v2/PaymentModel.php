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
}
