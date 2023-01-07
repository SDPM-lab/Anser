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



    /**
     * Product Create
     *
     * @param string $name
     * @param int $price
     * @param int $amount
     * @return void
     */
    public function createProductTransaction(string $name, int $price, int $amount)
    {
        $productData = [
            "name" => $name,
            "price" => $price,
            "amount" => $amount,
            "created_at" => date("Y-m-d H:i:s"),
            "updated_at" => date("Y-m-d H:i:s")
        ];

        try {
            $this->db->transStart();

            $this->db->table("product")
            ->insert($productData);

            $result = $this->db->transComplete();

            return $result;
        } catch (\Exception $e) {
            log_message('error', '[ERROR] {exception}', ['exception' => $e]);
            return false;
        }
    }

    /**
     * add product amount
     *
     * @param integer $p_key
     * @param integer $addAmount
     * @param integer $nowAmount
     * @return boolean
     */
    public function addInventoryTransaction(int $p_key, int $addAmount, int $nowAmount): bool
    {
        try {
            $this->db->transStart();

            $inventory = [
                "amount" => $nowAmount + $addAmount,
                "updated_at" => date("Y-m-d H:i:s")
            ];

            $this->db->table("product")
                     ->where("p_key", $p_key)
                     ->update($inventory);

            $result = $this->db->transComplete();
        } catch (\Exception $e) {
            log_message('error', '[ERROR] {exception}', ['exception' => $e]);
            return false;
        }
        return $result;
    }

    /**
     * reduce amount transaction
     *
     * @param integer $p_key
     * @param integer $reduceAmount
     * @param integer $nowAmount
     * @return boolean
     */
    public function reduceInventoryTransaction(int $p_key, int $reduceAmount, int $nowAmount): bool
    {
        try {
            $this->db->transBegin();

            $inventory = [
                "amount" => $nowAmount - $reduceAmount,
                "updated_at" => date("Y-m-d H:i:s")
            ];

            $this->db->table("product")
                     ->where("p_key", $p_key)
                     ->where("amount >=", $reduceAmount)
                     ->update($inventory);

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
}
