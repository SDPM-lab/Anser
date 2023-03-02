<?php

namespace App\Controllers\v2;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Models\v2\ProductModel;
use App\Entities\v2\ProductEntity;
use App\Models\v2\InventoryHistoryModel;

class ProductController extends BaseController
{
    use ResponseTrait;

    /**
     * [GET] api/v2/product/
     * Get all product.
     *
     * @return void
     */
    public function index()
    {
        $limit  = $this->request->getGet("limit") ?? 10;
        $offset = $this->request->getGet("offset") ?? 0;
        $search = $this->request->getGet("search") ?? "";
        $isDesc = $this->request->getGet("isDesc") ?? "desc";

        $productEntity = new ProductEntity();
        $productModel  = new ProductModel();

        $query = $productModel->orderBy("created_at", $isDesc ? "DESC" : "ASC");
        if ($search !== "") {
            $query->like("name", $search);
        }
        $dataCount = $query->countAllResults(false);
        $product   = $query->findAll($limit, $offset);

        $data = [
            "list"      => [],
            "dataCount" => $dataCount
        ];

        if ($product) {
            foreach ($product as $productEntity) {
                $productData = [
                    "p_key"       => $productEntity->p_key,
                    "name"        => $productEntity->name,
                    "price"       => $productEntity->price,
                    "amount"      => $productEntity->amount,
                    "createdAt"   => $productEntity->createdAt,
                    "updatedAt"   => $productEntity->updatedAt
                ];
                $data["list"][] = $productData;
            }
        } else {
            return $this->fail("Product data not found", 404);
        }

        return $this->respond([
            "status" => true,
            "data"   => $data,
            "msg"    => "Product index method successful."
        ]);
    }


    /**
     * [GET] api/v2/product/{p_key}
     * Get someone product by p_key.
     *
     * @param integer $p_key
     * @return void
     */
    public function show($p_key = null)
    {
        if (is_null($p_key)) {
            return $this->fail("The Product key is required", 400);
        }

        $productEntity = new ProductEntity();
        $productModel  = new ProductModel();

        $productEntity = $productModel->find($p_key);

        if ($productEntity) {
            $data = [
                "p_key"       => $productEntity->p_key,
                "name"        => $productEntity->name,
                "price"       => $productEntity->price,
                "amount"      => $productEntity->amount,
                "createdAt"   => $productEntity->createdAt,
                "updatedAt"   => $productEntity->updatedAt
            ];
        } else {
            return $this->fail("Product data not found", 404);
        }

        return $this->respond([
            "data" => $data,
            "msg"  => "Product show method successful."
        ]);
    }

    /**
     * [POST] api/v2/product/
     * Create product.
     *
     * @return void
     */
    public function create()
    {
        $data   = $this->request->getJSON(true);

        $name   = $data["name"]   ?? null;
        $price  = $data["price"]  ?? null;
        $amount = $data["amount"] ?? null;

        if (is_null($name) || is_null($price) || is_null($amount)) {
            return $this->fail("Incoming data error", 404);
        }

        $productModel = new ProductModel();

        $productData = [
            "name"       => $name,
            "price"      => $price,
            "amount"     => $amount,
            "created_at" => date("Y-m-d H:i:s"),
            "updated_at" => date("Y-m-d H:i:s")
        ];

        $productCreateResult =  $productModel->insert($productData);

        if ($productCreateResult) {
            return $this->respond([
                "status" => true,
                "data"   => $productCreateResult,
                "msg"    => "Product create method successful."
            ]);
        } else {
            return $this->fail("The product create failed, please try again.", 400);
        }
    }

    /**
     * [PUT] api/v2/product/{p_key}
     * Update someone product by p_key.
     *
     * @param integer $p_key
     * @return void
     */
    public function update($p_key = null)
    {
        if (is_null($p_key)) {
            return $this->fail("The Product key is required", 400);
        }

        $data = $this->request->getJSON(true);

        $name         = $data["name"]  ?? null;
        $price        = $data["price"] ?? null;

        $productEntity = new ProductEntity();
        $productModel  = new ProductModel();

        if (is_null($name) && is_null($price)) {
            return $this->fail("Incoming data error", 404);
        }

        $productEntity = $productModel->find($p_key);
        if (is_null($productEntity)) {
            return $this->fail("This product not found", 404);
        }

        $productEntity->p_key = $p_key;
        if (!is_null($name)) {
            $productEntity->name = $name;
        }
        if (!is_null($price)) {
            $productEntity->price = $price;
        }

        $result = $productModel->where('p_key', $productEntity->p_key)
                               ->save($productEntity);

        if ($result) {
            return $this->respond([
                "status" => true,
                "msg"    => "update method successful."
            ]);
        } else {
            return $this->fail("update method fail.", 400);
        }
    }

    /**
     * [DELETE] api/v2/product/{p_key}
     * Delete product by p_key.
     *
     * @param integer $p_key
     * @return void
     */
    public function delete($p_key = null)
    {
        if (is_null($p_key)) {
            return $this->fail("The Product key is required", 404);
        }

        $productModel  = new ProductModel();

        $productEntity = $productModel->find($p_key);
        if (is_null($productEntity)) {
            return $this->fail("This product not found", 404);
        }

        $result = $productModel->delete($p_key);

        return $this->respond([
            "status" => true,
            "id"     => $result,
            "msg"    => "Product delete method successful."
        ]);
    }

    /**
     * [POST] /api/v2/inventory/addInventory
     * Add product amount.
     *
     * @return void
     */
    public function addInventory()
    {
        $data = $this->request->getJSON(true);

        $p_key     = $data["p_key"] ?? null;
        $addAmount = $data["addAmount"] ?? null;
        $orch_key  = $this->request->getHeaderLine("Orch-Key") ?? null;

        if (is_null($orch_key)) {
            return $this->fail("The orchestrator key is needed.", 404);
        }

        if (is_null($addAmount)) {
            return $this->fail("Incoming data not found", 404);
        }

        if (is_null($p_key) && is_null($orch_key)) {
            return $this->fail("The product key is required.", 400);
        }

        if (is_null($p_key)) {
            $inventoryHistoryModel = new InventoryHistoryModel();

            $inventoryHistoryData = $inventoryHistoryModel->where('orch_key', $orch_key)
                                                          ->first();
            
            if (is_null($inventoryHistoryData)) {
                return $this->fail("Cannot find the product key by using orch_key.", 404);
            }

            $p_key = $inventoryHistoryData->p_key;
        }

        $productionEntity = ProductModel::getProduct($p_key);
        if (is_null($productionEntity)) {
            return $this->fail("This product not found", 404);
        }

        $nowAmount = $productionEntity->amount;

        $productModel = new ProductModel();

        $productAmountAddResult = $productModel->addInventoryTransaction($p_key, $nowAmount, $addAmount, $orch_key);

        if (!$productAmountAddResult) {
            return $this->fail("This product amount add fail", 400);
        }

        return $this->respond([
            "status" => true,
            "msg"    => "Product amount add method successful."
        ]);
    }

    /**
     * [POST] /api/v2/inventory/reduceInventory
     * Reduce product amount.
     *
     * @return void
     */
    public function reduceInventory()
    {
        $data = $this->request->getJSON(true);

        $p_key        = $data["p_key"] ?? null;
        $reduceAmount = $data["reduceAmount"] ?? null;
        $orch_key     = $this->request->getHeaderLine("Orch-Key")??null;

        if (is_null($orch_key)) {
            return $this->fail("The orchestrator key is needed.", 404);
        }

        if (is_null($reduceAmount)) {
            return $this->fail("Incoming data not found", 404);
        }

        if (is_null($p_key) && is_null($orch_key)) {
            return $this->fail("The product key is required.", 400);
        }

        if (is_null($p_key)) {
            $inventoryHistoryModel = new InventoryHistoryModel();

            $inventoryHistoryData = $inventoryHistoryModel->where('orch_key', $orch_key)
                                                          ->first();
            $p_key = $inventoryHistoryData->p_key;
        }

        $productionEntity = ProductModel::getProduct($p_key);

        if (is_null($productionEntity)) {
            return $this->fail("This product not found", 404);
        }

        if ($productionEntity->amount < $reduceAmount) {
            return $this->fail("This product amount not enough", 400);
        }

        $productModel = new ProductModel();

        $productAmountReduceResult = $productModel->reduceInventoryTransaction($p_key, $productionEntity->amount, $reduceAmount, $orch_key);

        if (!$productAmountReduceResult) {
            return $this->fail("This product amount reduce fail", 400);
        }

        return $this->respond([
            "status" => true,
            "msg"    => "Product amount reduce method successful."
        ]);
    }
}
