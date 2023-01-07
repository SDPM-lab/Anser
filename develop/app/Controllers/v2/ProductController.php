<?php

namespace App\Controllers\v2;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Models\v2\ProductModel;
use App\Entities\v2\ProductEntity;

class ProductController extends BaseController
{
    use ResponseTrait;

    /**
     * [GET] api/v2/product/
     * index method
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
        $product = $query->findAll($limit, $offset);

        $data = [
            "list"   => [],
            "dataCount" => $dataCount
        ];

        if ($product) {
            foreach ($product as $productEntity) {
                $productData = [
                    "name"        => $productEntity->name,
                    "price"       => $productEntity->price,
                    "amount"       => $productEntity->amount,
                    "createdAt"   => $productEntity->createdAt,
                    "updatedAt"   => $productEntity->updatedAt
                ];
                $data["list"][] = $productData;
            }
        } else {
            return $this->fail("product data not found", 404);
        }


        return $this->respond([
            "status" => true,
            "data"  => $data,
            "msg" => "product index method successful."
        ]);
    }


    /**
     * [GET] api/v2/product/{p_key}
     *  get someone product by p_key
     *
     * @param integer $p_key
     * @return void
     */
    public function show($p_key = null)
    {
        if (is_null($p_key)) {
            return $this->fail("Incoming data(Product Key) not true", 400);
        }

        $productEntity = new ProductEntity();
        $productModel  = new ProductModel();

        $productEntity = $productModel->find($p_key);

        if ($productEntity) {
            $data = [
                "p_key"       => $productEntity->p_key,
                "name"        => $productEntity->name,
                "price"       => $productEntity->price,
                "amount"       => $productEntity->amount,
                "createdAt"   => $productEntity->createdAt,
                "updatedAt"   => $productEntity->updatedAt
            ];
        } else {
            return $this->fail("product data not found", 404);
        }

        return $this->respond([
            "data" => $data,
            "msg" => "product show method successful."
        ]);
    }

    /**
     * [POST] api/v2/product/
     * create product
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
            return $this->fail("Incoming data error", 400);
        }

        $productModel = new ProductModel();
        $productCreateResult =  $productModel->createProductTransaction($name, $price, $amount);

        if ($productCreateResult) {
            return $this->respond([
                "status" => true,
                "data" => $productCreateResult,
                "msg" => "product create method successful."
            ]);
        } else {
            return $this->fail("product create method fail.", 400);
        }
    }

    /**
     * [PUT] api/v2/product/{p_key}
     * update someone product by p_key
     *
     * @param integer $p_key
     * @return void
     */
    public function update($p_key = null)
    {
        if (is_null($p_key)) {
            return $this->fail("Incoming data not found", 400);
        }

        $data = $this->request->getJSON(true);

        $name         = $data["name"]  ?? null;
        $price        = $data["price"] ?? null;
        $amount       = $data["amount"] ?? null;

        $productEntity = new ProductEntity();
        $productModel  = new ProductModel();

        if (is_null($p_key)) {
            return $this->fail("Please enter product key", 404);
        }
        if (is_null($name) && is_null($price)&& is_null($amount)) {
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

        if (!is_null($amount)) {
            $productEntity->amount = $amount;
        }

        $result = $productModel->where('p_key', $productEntity->p_key)
            ->save($productEntity);

        if ($result) {
            return $this->respond([
                "status" => true,
                "msg" => "update method successful."
            ]);
        } else {
            return $this->fail("update method fail.", 400);
        }
    }

    /**
     * [DELETE] api/v2/product/{p_key}
     * delete someone product
     *
     * @param integer $p_key
     * @return void
     */
    public function delete($p_key = null)
    {
        if (is_null($p_key)) {
            return $this->fail("Please enter product key", 404);
        }

        $productModel  = new ProductModel();

        $productEntity = $productModel->find($p_key);
        if (is_null($productEntity)) {
            return $this->fail("This product not found", 404);
        }

        $result = $productModel->delete($p_key);

        return $this->respond([
            "status" => true,
            "id" => $result,
            "msg" => "product delete method successful."
        ]);
    }

    /**
     * [POST] /api/v2/inventory/addInventory
     * add product amount
     *
     * @return void
     */
    public function addInventory()
    {
        $data = $this->request->getJSON(true);

        $p_key     = $data["p_key"];
        $addAmount = $data["addAmount"];

        if (is_null($p_key)  || is_null($addAmount)) {
            return $this->fail("Incoming data not found", 404);
        }

        $productionEntity = ProductModel::getProduct($p_key);
        if (is_null($productionEntity)) {
            return $this->fail("This product not found", 404);
        }

        $nowAmount = $productionEntity->amount;

        $productModel = new ProductModel();

        $productAmountAddResult = $productModel->addInventoryTransaction($p_key, $addAmount, $nowAmount);
        if (!$productAmountAddResult) {
            return $this->fail("This product amount add fail", 400);
        }

        return $this->respond([
            "status" => true,
            "msg" => "product amount add method successful."
        ]);
    }

    /**
     * [POST] /api/v2/inventory/reduceInventory
     * reduce product amount
     *
     * @return void
     */

    public function reduceInventory()
    {
        $data = $this->request->getJSON(true);

        $p_key     = $data["p_key"];
        $reduceAmount = $data["reduceAmount"];

        if (is_null($p_key) || is_null($reduceAmount)) {
            return $this->fail("Incoming data not found", 404);
        }

        $productionEntity = ProductModel::getProduct($p_key);
        if (is_null($productionEntity)) {
            return $this->fail("This product not found", 404);
        }


        if (is_null($productionEntity)) {
            return $this->fail("This product not found", 404);
        }

        if ($productionEntity->amount < $reduceAmount) {
            return $this->fail("This product amount not enough", 400);
        }

        $productModel = new ProductModel();
        $productAmountReduceResult = $productModel->reduceInventoryTransaction($p_key, $reduceAmount, $productionEntity->amount);
        if (is_null($productAmountReduceResult)) {
            return $this->fail("This product amount reduce fail", 400);
        }

        return $this->respond([
            "status" => true,
            "msg" => "product amount reduce method successful."
        ]);
    }
}
