<?php

namespace SDPMlab\Anser\Orchestration\Saga\Cache;

use SDPMlab\Anser\Orchestration\Saga\Cache\Redis\PRedisHandler;
use SDPMlab\Anser\Orchestration\Saga\Cache\CacheHandlerInterface;
use SDPMlab\Anser\Exception\RedisException;

class CacheFactory
{
    const CACHE_DRIVER_PREDIS = "predis";

    /**
     * The mapping list of Cache Drivers instance.
     *
     * @var array
     */
    private static $cacheMapping = [
        "predis" => PRedisHandler::class,
    ];

    /**
     * The paramter of driver.
     *
     * @var string|null
     */
    protected static $driver = null;

    /**
     * The cache instance.
     *
     * @var CacheHandlerInterface|null
     */
    protected static $cacheInstance = null;

    /**
     * Initial the cache driver.
     *
     * @param string $driver
     * @param mixed $config
     * @param string|array|null $option
     * @return CacheHandlerInterface
     */
    public static function initCacheDriver(string $driver, $config = null, $option = null): CacheHandlerInterface
    {
        if (isset(self::$cacheMapping[$driver])) {
            self::$driver        = $driver;
            self::$cacheInstance = new self::$cacheMapping[$driver]($config, $option);
            return self::$cacheInstance;
        } else {
            throw RedisException::forCacheDriverNotFound($driver);
        }
    }

    /**
     * Get the constructed cache instance.
     *
     * @return CacheHandlerInterface|null
     */
    public static function getCacheInstance(): ?CacheHandlerInterface
    {
        return self::$cacheInstance;
    }
}
