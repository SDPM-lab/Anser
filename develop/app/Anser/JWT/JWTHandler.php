<?php

namespace App\Anser\JWT;

use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\Key;
use App\Anser\JWT\JWTConfig;
use Exception;
use stdClass;

class JWTHandler extends JWTConfig
{
    /**
     * Generate token.
     *
     * @param string $iss_time  Issuing time
     * @param string $exp_time  Expiration time
     * @param array|null $data  Passing Data
     * @return string
     */
    public function generateToken(string $iss_time, string $exp_time, array $data = null): string
    {
        $payload = [
            'iss'  => $this->iss,
            'aud'  => $this->aud,
            'sub'  => $this->sub,
            'iat'  => $iss_time,
            'ext'  => $exp_time,
            'data' => $data
        ];

        $token = JWT::encode($payload, $this->JWT_SECRET, $this->alg);

        return $token;
    }

    /**
     * Parsing the token to get the original data.
     *
     * @param string $token
     * @param string|null $jwt_secret
     * @return stdClass
     */
    public function decodeToken(string $token, string $jwt_secret = null): stdClass
    {
        $verify_secret = is_null($jwt_secret) ? $this->JWT_SECRET : $jwt_secret;

        try {
            $decoded = JWT::decode($token, new Key($verify_secret, $this->alg));
        } catch (\Exception $e) {
            throw new Exception('Token Error : ' . $e->getMessage());
        }

        return $decoded;
    }
}
