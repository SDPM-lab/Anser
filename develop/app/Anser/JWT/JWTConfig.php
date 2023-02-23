<?php

namespace App\Anser\JWT;

class JWTConfig
{
    /**
     * JWT SECRET TOKEN
     *
     * @var string
     */
    protected $JWT_SECRET = 'JWT SECRET TOKEN';

    /**
     * Issuer of the JWT
     *
     * @var string
     */
    protected string $iss = 'my_anser_service';

    /**
     * Audience that the JWT
     *
     * @var string
     */
    protected string $aud = 'my_anser_service';

    /**
     * Subject of the JWT
     *
     * @var string
     */
    protected string $sub = 'my_anser_service';

    /**
     * alg to be used
     *
     * @var string
     */
    protected string $alg = "HS256";
}
