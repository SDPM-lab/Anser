<?php

namespace App\Models\v2;

use CodeIgniter\Model;
use App\Entities\v2\ProductEntity;

class ProductModel extends Model
{
    protected $DBGroup          = USE_DB_GROUP;
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

    public function addInventoryTransaction(int $p_key,int $nowAmount,int $addAmount,string $orch_key)
    {
        $now = date("Y-m-d H:i:s");

        $inventory_history = [
            "type"       => "increaseInventoryAmount",
            "p_key"      => $p_key,
            "amount"     => $addAmount,
            "orch_key"   => $orch_key,
            "created_at" => $now,
            "updated_at" => $now,
        ];

        try {
            $this->db->transStart();

            $inventory_data = [
                "amount"     => $nowAmount + $addAmount,
                "updated_at" => date("Y-m-d H:i:s")
            ];

            $this->db->table("product")
                     ->where("p_key", $p_key)
                     ->set($inventory_data)
                     ->update();

            $this->db->table("inventory_history")
                     ->insert($inventory_history);

            $result = $this->db->transComplete();

            return $result;
        } catch (\Exception $e) {
            log_message('error', '[ERROR] {exception}', ['exception' => $e]);
            return false;
        }
    }

    public function reduceInventoryTransaction(int $p_key,int $nowAmount,int $reduceAmount,string $orch_key)
    {
        $now = date("Y-m-d H:i:s");

        $inventory_history = [
            "type"       => "reduceInventoryAmount",
            "p_key"      => $p_key,
            "amount"     => $reduceAmount,
            "orch_key"   => $orch_key,
            "created_at" => $now,
            "updated_at" => $now,
        ];

        try {
            $this->db->transStart();

            $inventory_data = [
                "amount"     => $nowAmount - $reduceAmount,
                "updated_at" => date("Y-m-d H:i:s")
            ];
            $this->db->table("product")
                     ->where("p_key", $p_key)
                     ->where("amount >=", $reduceAmount)
                     ->set($inventory_data)
                     ->update();

            $this->db->table("inventory_history")
                     ->insert($inventory_history);

            $result = $this->db->transComplete();

            return $result;
        } catch (\Exception $e) {
            log_message('error', '[ERROR] {exception}', ['exception' => $e]);
            return false;
        }
    }
}
