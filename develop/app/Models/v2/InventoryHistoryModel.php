<?php

namespace App\Models\v2;

use CodeIgniter\Model;
use App\Entities\v2\InventoryHistoryEntity;

class InventoryHistoryModel extends Model
{
    protected $DBGroup          = USE_DB_GROUP;
    protected $table            = 'inventory_history';
    protected $primaryKey       = 'ih_key';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = InventoryHistoryEntity::class;
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = ['ih_key','type','p_key','amount','orch_key'];

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
