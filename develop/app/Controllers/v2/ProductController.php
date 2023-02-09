<?php

namespace App\Controllers\v2;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Models\v2\ProductModel;
use App\Entities\v2\ProductEntity;
use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Product Service API",
 *     version="0.1.0"
 * )
 * @OA\Server(
 *       url = "http://localhost:8080",
 *       description="local"
 * )
 */
class ProductController extends BaseController
{
    use ResponseTrait;

    /**
     * [GET] api/v2/product/
     * Get all product.
     *
     * @return void
     */
    /**
     * @OA\Get(
     *     path="/api/v2/product",
     *     tags={"Product"},
     *     description="Get list of product",
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="limit",
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="offset",
     *         in="query",
     *         required=false,
     *         description="offset",
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="search",
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="isDesc",
     *         in="query",
     *         required=false,
     *         description="isDesc",
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Product index method successful.",
     *         @OA\JsonContent(type="object",
     *              @OA\Property(property="status", type="bool"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="list", type="array",
     *                      @OA\Items(type="object",
     *                          @OA\Property(property="p_key", type="integer",example="1"),
     *                          @OA\Property(property="name", type="string",example="Awesome Wool Knife"),
     *                          @OA\Property(property="price", type="integer",example="846"),
     *                          @OA\Property(property="amount", type="integer",example="190"),
     *                          @OA\Property(property="createdAt", type="string",example="2023-02-06 05:52:20"),
     *                          @OA\Property(property="updatedAt", type="string",example="2023-02-08 09:37:44"),
     *                      ),
     *                  ),
     *                  @OA\Property(property="dataCount", type="integer",example="20")
     *              ),
     *              @OA\Property(property="msg", type="string",example="Product index method successful."),
     *          ),
     *     ),
     *     @OA\Response(
     *          response=404,
     *          description="Product data not found",
     *          @OA\JsonContent(type="object",
     *              @OA\Property(property="status", type="string",description="404",example="404"),
     *              @OA\Property(property="error", type="string",description="404",example="404"),
     *              @OA\Property(property="messages", type="object",
     *                  @OA\Property(property="error", type="string",description="Product data not found",example="Product data not found"),
     *              ),
     *          ),  
     *     ),
     * )
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
    /**
     * @OA\Get(
     *     path="/api/v2/product/{p_key}",
     *     tags={"Product"},
     *     description="Get list of product",
     *     @OA\Parameter(
     *         name="p_key",
     *         in="path",
     *         required=true,
     *         description="p_key",
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Product show method successful.",
     *         @OA\JsonContent(type="object",
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="p_key", type="integer",example="2"),
     *                  @OA\Property(property="name", type="string",example="Mediocre Silk Shoes"),
     *                  @OA\Property(property="price", type="integer",example="617"),
     *                  @OA\Property(property="amount", type="integer",example="266"),
     *                  @OA\Property(property="createdAt", type="integer",example="2023-01-26 10:31:50"),
     *                  @OA\Property(property="updatedAt", type="integer",example="2023-02-08 09:37:44"),
     *              ),
     *              @OA\Property(property="msg", type="string",example="Product show method successful."),
     *          ),
     *     ),
     *     @OA\Response(
     *          response=400,
     *          description="The Product key is required",
     *          @OA\JsonContent(type="object",
     *              @OA\Property(property="status", type="string",description="400",example="400"),
     *              @OA\Property(property="error", type="string",description="400",example="400"),
     *              @OA\Property(property="messages", type="object",
     *                  @OA\Property(property="error", type="string",description="The Product key is required",example="The Product key is required"),
     *              ),
     *          ),  
     *     ),
     *     @OA\Response(
     *          response=404,
     *          description="Product data not found",
     *          @OA\JsonContent(type="object",
     *              @OA\Property(property="status", type="string",description="404",example="404"),
     *              @OA\Property(property="error", type="string",description="404",example="404"),
     *              @OA\Property(property="messages", type="object",
     *                  @OA\Property(property="error", type="string",description="Product data not found",example="Product data not found"),
     *              ),
     *          ),  
     *     ),
     * )
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
    /**
     * @OA\POST(
     *     path="/api/v2/product",
     *     tags={"Product"},
     *     description="Create product.",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Request body",
     *         @OA\JsonContent(type="object",
     *                  @OA\Property(property="name", type="string",example="Mediocre Silk Shoes"),
     *                  @OA\Property(property="price", type="integer",example="617"),
     *                  @OA\Property(property="amount", type="integer",example="266"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Product show method successful.",
     *         @OA\JsonContent(type="object",
     *              @OA\Property(property="status", type="string",example="true"),
     *              @OA\Property(property="data", type="integer",example="22"),
     *              @OA\Property(property="msg", type="string",example="Product show method successful."),
     *          ),
     *     ),
     *     @OA\Response(
     *          response=404,
     *          description="Incoming data error",
     *          @OA\JsonContent(type="object",
     *              @OA\Property(property="status", type="string",description="400",example="404"),
     *              @OA\Property(property="error", type="string",description="400",example="404"),
     *              @OA\Property(property="messages", type="object",
     *                  @OA\Property(property="error", type="string",description="Incoming data error",example="Incoming data error"),
     *              ),
     *          ),  
     *     ),
     *     @OA\Response(
     *          response=400,
     *          description="The product create failed, please try again.",
     *          @OA\JsonContent(type="object",
     *              @OA\Property(property="status", type="string",description="404",example="400"),
     *              @OA\Property(property="error", type="string",description="404",example="400"),
     *              @OA\Property(property="messages", type="object",
     *                  @OA\Property(property="error", type="string",description="The product create failed, please try again.",example="The product create failed, please try again."),
     *              ),
     *          ),  
     *     ),
     * )
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
    /**
     * @OA\PUT(
     *     path="/api/v2/product/{p_key}",
     *     tags={"Product"},
     *     description="Update someone product by p_key.",
     *     @OA\Parameter(
     *         name="p_key",
     *         in="path",
     *         required=true,
     *         description="p_key",
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Request body",
     *         @OA\JsonContent(type="object",
     *              @OA\Property(property="name", type="string",example="Mediocre Silk Shoes"),
     *              @OA\Property(property="price", type="integer",example="617"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Product show method successful.",
     *         @OA\JsonContent(type="object",
     *              @OA\Property(property="status", type="string",example="true"),
     *              @OA\Property(property="msg", type="string",example="update method successful."),
     *          ),
     *     ),
     *     @OA\Response(
     *          response=404,
     *          description="Incoming data error",
     *          @OA\JsonContent(
     *              @OA\Examples(
     *                  example="string",
     *                  value = {
     *                      "status": "404",
     *                      "error" : "404",
     *                      "messages": {
     *                          "error": "Incoming data error"
     *                      }
     *                  },
     *                  summary="Incoming data error"
     *              ),
     *              @OA\Examples(
     *                  example="string2",
     *                  value = {
     *                      "status": "404",
     *                      "error" : "404",
     *                      "messages": {
     *                          "error": "This product not found"
     *                      }
     *                  },
     *                  summary="This product not found"
     *              ),
     *          ), 
     *     ),
     *     @OA\Response(
     *          response=400,
     *          description="The product create failed, please try again.",
     *          @OA\JsonContent(
     *              @OA\Examples(
     *                  example="string",
     *                  value = {
     *                      "status": "400",
     *                      "error" : "400",
     *                      "messages": {
     *                          "error": "The Product key is required"
     *                      }
     *                  },
     *                  summary="The Product key is required"
     *              ),
     *              @OA\Examples(
     *                  example="string2",
     *                  value = {
     *                      "status": "400",
     *                      "error" : "400",
     *                      "messages": {
     *                          "error": "update method fail."
     *                      }
     *                  },
     *                  summary="update method fail."
     *              ),
     *          ),   
     *     ),
     * )
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
    /**
     * @OA\DELETE(
     *     path="/api/v2/product/{p_key}",
     *     tags={"Product"},
     *     description="Update someone product by p_key.",
     *     @OA\Parameter(
     *         name="p_key",
     *         in="path",
     *         required=true,
     *         description="p_key",
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Product delete method successful.",
     *         @OA\JsonContent(type="object",
     *              @OA\Property(property="status", type="string",example="true"),
     *              @OA\Property(property="msg", type="string",example="Product delete method successful."),
     *          ),
     *     ),
     *     @OA\Response(
     *          response=404,
     *          description="Delete product 404 error",
     *          @OA\JsonContent(
     *              @OA\Examples(
     *                  example="string",
     *                  value = {
     *                      "status": "404",
     *                      "error" : "404",
     *                      "messages": {
     *                          "error": "The Product key is required"
     *                      }
     *                  },
     *                  summary="The Product key is required"
     *              ),
     *              @OA\Examples(
     *                  example="string2",
     *                  value = {
     *                      "status": "404",
     *                      "error" : "404",
     *                      "messages": {
     *                          "error": "This product not found"
     *                      }
     *                  },
     *                  summary="This product not found"
     *              ),
     *          ), 
     *     ),
     * )
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
    /**
     * @OA\POST(
     *     path="/api/v2/inventory/addInventory",
     *     tags={"Inventory"},
     *     description="Add product amount.",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Request body",
     *         @OA\JsonContent(type="object",
     *              @OA\Property(property="p_key", type="integer",example="22"),
     *              @OA\Property(property="addAmount", type="integer",example="10"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Product delete method successful.",
     *         @OA\JsonContent(type="object",
     *              @OA\Property(property="status", type="string",example="true"),
     *              @OA\Property(property="msg", type="string",example="Product delete method successful."),
     *          ),
     *     ),
     *     @OA\Response(
     *          response=404,
     *          description="Add Inventory 404 error",
     *          @OA\JsonContent(
     *              @OA\Examples(
     *                  example="string",
     *                  value = {
     *                      "status": "404",
     *                      "error" : "404",
     *                      "messages": {
     *                          "error": "Incoming data not found"
     *                      }
     *                  },
     *                  summary="Incoming data not found"
     *              ),
     *              @OA\Examples(
     *                  example="string2",
     *                  value = {
     *                      "status": "404",
     *                      "error" : "404",
     *                      "messages": {
     *                          "error": "This product not found"
     *                      }
     *                  },
     *                  summary="This product not found"
     *              ),
     *          ),   
     *     ),
     *     @OA\Response(
     *          response=400,
     *          description="This product amount add fail",
     *          @OA\JsonContent(type="object",
     *              @OA\Property(property="status", type="string",description="400",example="400"),
     *              @OA\Property(property="error", type="string",description="400",example="400"),
     *              @OA\Property(property="messages", type="object",
     *                  @OA\Property(property="error", type="string",description="This product amount add fail",example="This product amount add fail"),
     *              ),
     *          ),  
     *     ),
     * )
     */
    public function addInventory()
    {
        $data = $this->request->getJSON(true);

        $p_key     = $data["p_key"] ?? null;
        $addAmount = $data["addAmount"] ?? null;

        if (is_null($p_key)  || is_null($addAmount)) {
            return $this->fail("Incoming data not found", 404);
        }

        $productionEntity = ProductModel::getProduct($p_key);
        if (is_null($productionEntity)) {
            return $this->fail("This product not found", 404);
        }

        $nowAmount = $productionEntity->amount;

        $productModel = new ProductModel();

        $inventory = [
            "amount"     => $nowAmount + $addAmount,
            "updated_at" => date("Y-m-d H:i:s")
        ];
        $productAmountAddResult = $productModel->where("p_key", $p_key)
                                               ->set($inventory)
                                               ->update();

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
    /**
     * @OA\POST(
     *     path="/api/v2/inventory/reduceInventory",
     *     tags={"Inventory"},
     *     description="Reduce product amount.",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Request body",
     *         @OA\JsonContent(type="object",
     *              @OA\Property(property="p_key", type="integer",example="22"),
     *              @OA\Property(property="reduceAmount", type="integer",example="10"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Product delete method successful.",
     *         @OA\JsonContent(type="object",
     *              @OA\Property(property="status", type="string",example="true"),
     *              @OA\Property(property="msg", type="string",example="Product delete method successful."),
     *          ),
     *     ),
     *     @OA\Response(
     *          response=404,
     *          description="Reduce Inventory 404 error",
     *          @OA\JsonContent(
     *              @OA\Examples(
     *                  example="string",
     *                  value = {
     *                      "status": "404",
     *                      "error" : "404",
     *                      "messages": {
     *                          "error": "Incoming data not found"
     *                      }
     *                  },
     *                  summary="Incoming data not found"
     *              ),
     *              @OA\Examples(
     *                  example="string2",
     *                  value = {
     *                      "status": "404",
     *                      "error" : "404",
     *                      "messages": {
     *                          "error": "This product not found"
     *                      }
     *                  },
     *                  summary="This product not found"
     *              ),
     *          ),  
     *     ),
     *     @OA\Response(
     *          response=400,
     *          description="Reduce Inventory 400 error",
     *          @OA\JsonContent(
     *              @OA\Examples(
     *                  example="string",
     *                  value = {
     *                      "status": "400",
     *                      "error" : "400",
     *                      "messages": {
     *                          "error": "This product amount not enough"
     *                      }
     *                  },
     *                  summary="This product amount not enough"
     *              ),
     *              @OA\Examples(
     *                  example="string2",
     *                  value = {
     *                      "status": "400",
     *                      "error" : "400",
     *                      "messages": {
     *                          "error": "This product amount reduce fail"
     *                      }
     *                  },
     *                  summary="This product amount reduce fail"
     *              ),
     *          ),  
     *     ),
     * )
     */
    public function reduceInventory()
    {
        $data = $this->request->getJSON(true);

        $p_key        = $data["p_key"] ?? null;
        $reduceAmount = $data["reduceAmount"] ?? null;

        if (is_null($p_key) || is_null($reduceAmount)) {
            return $this->fail("Incoming data not found", 404);
        }

        $productionEntity = ProductModel::getProduct($p_key);

        if (is_null($productionEntity)) {
            return $this->fail("This product not found", 404);
        }

        if ($productionEntity->amount < $reduceAmount) {
            return $this->fail("This product amount not enough", 400);
        }

        $productModel = new ProductModel();

        $inventory = [
            "amount"     => $productionEntity->amount - $reduceAmount,
            "updated_at" => date("Y-m-d H:i:s")
        ];
        $productAmountReduceResult = $productModel->where("p_key", $p_key)
                                                  ->where("amount >=", $reduceAmount)
                                                  ->set($inventory)
                                                  ->update();

        if (!$productAmountReduceResult) {
            return $this->fail("This product amount reduce fail", 400);
        }

        return $this->respond([
            "status" => true,
            "msg"    => "Product amount reduce method successful."
        ]);
    }
}
