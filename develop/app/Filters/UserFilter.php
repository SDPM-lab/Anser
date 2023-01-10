<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Services\UserService;

class UserFilter implements FilterInterface
{
    private $response;

    public function __construct()
    {
        $this->response = \CodeIgniter\Config\Services::response();
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

        if (empty($user_key)) {
            return $this->response->setStatusCode(401, 'Unauthorized')->setJSON($failBody);
        }

        $userWalletEntity = UserService::verifyUserIsExist($user_key);

        if (is_null($userWalletEntity)) {
            return $this->response->setStatusCode(404, 'Unauthorized')->setJSON($userNotExistBody);
        }

        UserService::setUserKey($userWalletEntity->u_key);
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
        //
    }
}
