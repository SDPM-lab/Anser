<?php

namespace App\Filters;

use App\Services\TokenService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Services\UserService;

class AnserTokenFilter implements FilterInterface
{
    private $response;
    private $request;
    private $db;
    
    public function __construct()
    {
        $this->response = \CodeIgniter\Config\Services::response();
        $this->request  = \Config\Services::request();
    }

    /**
     * Do whatever processing this filter needs to do.
     * By default it should not return anything during
     * normal execution. However, when an abnormal state
     * is found, it should return an instance of
     * CodeIgniter\HTTP\Response. If it does, script
     * execution will end and that Response will be
     * sent back to the client, allowing for error pages,
     * redirects, etc.
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $request = \Config\Services::request();

        $user_key = $request->getHeaderLine("X-User-Key");

        $anser_token = $request->getHeaderLine("Anser-Token");

        $failBody = [
            "status" => 401,
            "error"  => 401,
            "message" => [
                "error" => "Please enter user key!"
            ]
        ];

        $userNotExistBody = [
            "status" => 404,
            "error"  => 404,
            "message" => [
                "error" => "This User is not exist!"
            ]
        ];

        $tokenEmptyBody = [
            "status" => 401,
            "error"  => 401,
            "message" => [
                "error" => "Please enter anser token!"
            ]
        ];

        if (empty($user_key)) {
            return $this->response->setStatusCode(401, 'Unauthorized')->setJSON($failBody);
        }

        $userWalletEntity = UserService::verifyUserIsExist($user_key);

        if (is_null($userWalletEntity)) {
            return $this->response->setStatusCode(404, 'Unauthorized')->setJSON($userNotExistBody);
        }

        UserService::setUserKey($userWalletEntity->u_key);

        if (empty($anser_token)) {
            return $this->response->setStatusCode(401, 'Unauthorized')->setJSON($tokenEmptyBody);
        }

        $data = TokenService::decodeAndSetToken($anser_token);

        TokenService::setOrchKey($data->data->orch_key);
    }

    /**
     * Allows After filters to inspect and modify the response
     * object as needed. This method does not allow any way
     * to stop execution of other after filters, short of
     * throwing an Exception or Error.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return mixed
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $this->db = \Config\Database::connect();

        $anser_token     = $request->getHeaderLine("Anser-Token");
        $request_path    = $request->getUri()->getPath();
        $request_method  = $request->getMethod();
        $response_status = $response->getStatusCode();
        $orch_key        = TokenService::getOrchKey();
        $request_api     = $request->getUri()->getSegments()[2];

        if ($request_api == "order") {
            $this->orderHistory($request_path, $request_method, $response_status, $orch_key);
        }

        if ($request_api == "payment") {
            $this->paymentHistory($request_path, $request_method, $response_status, $orch_key);
        }

        $response->setHeader("Anser-Token", $anser_token);
    }

    /**
     * Add order api call record.
     *
     * @param string $request_path
     * @param string $request_method
     * @param string $response_status
     * @param string $orch_key
     * @return void
     */
    public function orderHistory(string $request_path, string $request_method, string $response_status, string $orch_key)
    {
        $response_data = json_decode($this->response->getBody());

        $o_key = "";

        if ($request_method === "post") {
            if ($response_status == 200) {
                $o_key = $response_data->orderID;
            }
        } elseif ($request_method === "put") {
            $o_key = $this->request->getUri()->getSegments()[3];
        } elseif ($request_method === "delete") {
            $o_key = $this->request->getUri()->getSegments()[3];
        }

        $history_data = [
            "path"       => $request_path,
            "method"     => $request_method,
            "status"     => $response_status,
            "o_key"      => $o_key,
            "orch_key"   => $orch_key,
            "created_at" => date("Y-m-d H:i:s"),
            "updated_at" => date("Y-m-d H:i:s")
        ];

        $this->db->table("order_history")
                 ->insert($history_data);
    }

    /**
     * Add payment api call record.
     *
     * @param string $request_path
     * @param string $request_method
     * @param string $response_status
     * @param string $orch_key
     * @return void
     */
    public function paymentHistory(string $request_path, string $request_method, string $response_status, string $orch_key)
    {
        $response_data = json_decode($this->response->getBody());

        $pm_key = "";

        if ($request_method === "post") {
            if ($response_status == 200) {
                $pm_key = $response_data->paymentID;
            }
        } elseif ($request_method === "put") {
            $pm_key = $this->request->getUri()->getSegments()[3];
        } elseif ($request_method === "delete") {
            $pm_key = $this->request->getUri()->getSegments()[3];
        }

        $history_data = [
            "path"       => $request_path,
            "method"     => $request_method,
            "status"     => $response_status,
            "pm_key"     => $pm_key,
            "orch_key"   => $orch_key,
            "created_at" => date("Y-m-d H:i:s"),
            "updated_at" => date("Y-m-d H:i:s")
        ];

        $this->db->table("payment_history")
                 ->insert($history_data);
    }
}
