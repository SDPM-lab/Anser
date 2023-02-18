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
     * @param string|array|null $config
     * @param string|array|null $option
     * @return CacheHandlerInterface
     */
    public static function initCacheDriver(string $driver, $config = null, $option = null): CacheHandlerInterface
    {
        $cacheDriver = ucfirst(strtolower($driver));

        if (isset(self::$cacheMapping[$cacheDriver])) {
            self::$driver        = $driver;
            self::$cacheInstance = new self::$cacheMapping[$cacheDriver]($config, $option);
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
