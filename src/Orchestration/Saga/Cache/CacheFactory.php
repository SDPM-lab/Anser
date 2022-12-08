<?php

namespace SDPMlab\Anser\Orchestration\Saga\Cache;

use SDPMlab\Anser\Orchestration\Saga\Cache\Redis\RedisHandler;
use SDPMlab\Anser\Orchestration\Saga\Cache\CacheHandlerInterface;
use SDPMlab\Anser\Exception\RedisException;

class CacheFactory
{
    /**
     * The mapping list of Cache Drivers instance.
     *
     * @var array
     */
    private static $cacheMapping = [
        "Redis" => RedisHandler::class
    ];

    /**
     * The paramter of driver.
     *
     * @var string
     */
    protected static $driver = '';

    /**
     * Initial the cache driver.
     *
     * @param string $driver
     * @param string|array|null $config
     * @param string|array|null $option
     * @return CacheHandlerInterface
     */
    public static function initCacheDriver(string $driver, $config = null, $option = null): CacheHandlerInterface
    {
        $cacheDriver = ucfirst(strtolower($driver));

        if (isset(self::$cacheMapping[$cacheDriver])) {
            self::$driver = new self::$cacheMapping[$cacheDriver]($config, $option);
            return self::$driver;
        } else {
            throw RedisException::forCacheDriverNotFound($driver);
        }
    }
}