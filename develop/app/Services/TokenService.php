<?php

namespace App\Services;

use App\Anser\JWT\JWTHandler;
use stdClass;

class TokenService
{
    /**
     * orch key
     *
     * @var null|string
     */
    private static $orch_key;

    /**
     * anser token
     *
     * @var null|string
     */
    private static $anser_token;

    public static function decodeAndSetToken(string $anser_token): stdClass
    {
        $jwt_handler = new JWTHandler();

        self::$anser_token = $anser_token;

        $data = $jwt_handler->decodeToken(self::$anser_token, getenv('JWT_SECRET'));

        return $data;
    }


    /**
     * 取得orch key
     *
     * @return string|null
     */
    public static function getOrchKey(): ?string
    {
        return self::$orch_key;
    }

    /**
     * 設定orch key
     *
     * @param string $orch_key
     * @return void
     */
    public static function setOrchKey(string $orch_key)
    {
        self::$orch_key = $orch_key;
    }
}
