<?php

namespace App\Models\v2;

use CodeIgniter\Model;
use App\Entities\v2\OrderHistoryEntity;

class OrderHistoryModel extends Model
{
    protected $DBGroup          = USE_DB_GROUP;
    protected $table            = 'order_history';
    protected $primaryKey       = 'oh_key';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = OrderHistoryEntity::class;
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = ['oh_key','type','o_key','orch_key'];

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
