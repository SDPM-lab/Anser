<?php

namespace SDPMlab\Anser\Orchestration\Saga\Cache;

use Exception;

abstract class BaseCacheHandler implements CacheHandlerInterface
{
    /**
     * Undocumented variable
     *
     * @var string
     */
    protected $handler;

    /**
     * Hostname
     *
     * @var string
     */
    protected $hostname;

    /**
     * Port
     *
     * @var int|string
     */
    protected $port = '';

    /**
     * Connect timeout
     *
     * @var int
     */
    protected $connectTimeout;

    /**
     * Username
     *
     * @var string
     */
    protected $username;

    /**
     * Password
     *
     * @var string
     */
    protected $password;

    /**
     * SSL context options
     *
     * @var array|null
     */
    protected $ssl;

    /**
     * The option of Backoff algorithms
     *
     * @var array|null
     */
    protected $backoff;

    /**
     * Set cache driver.
     *
     */
    protected $driver = '';

    public function __construct(string $driver)
    {
        $cacheDriverName = ucfirst(strtolower($driver));
        $cacheDriverPath = __DIR__ . '/' . $cacheDriverName . '/' . $cacheDriverName . 'Handler.php';

        if (file_exists($cacheDriverPath)) {
            require_once $cacheDriverPath;

            $this->driver = new $cacheDriverPath($this);
        } else {
            throw new Exception('建構 ' . $className . ' 時發生錯誤，請重新再試');
        }
    }
}
