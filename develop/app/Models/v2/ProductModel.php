<?php

namespace App\Models\v2;

use CodeIgniter\Model;
use App\Entities\v2\ProductEntity;

class ProductModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'product';
    protected $primaryKey       = 'p_key';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = ProductEntity::class;
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = ['p_key', 'name', 'price', 'amount'];

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
     * Get Product By productID
     *
     * @param string $productID
     * @return ProductEntity|null
     */
    public static function getProduct(int $productID): ?ProductEntity
    {
        $productModel = new ProductModel();

        $productEntity = $productModel->find($productID);

        return $productEntity;
    }
}
