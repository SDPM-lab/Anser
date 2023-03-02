<?php

namespace SDPMlab\Anser\Exception;

use SDPMlab\Anser\Exception\AnserException;

class RedisException extends AnserException
{
    /**
     * 初始化　RedisException
     *
     * @param string $message 錯誤訊息
     */
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function forCacheDriverNotFound($alias): RedisException
    {
        return new self("傳入快取驅動程式名稱- {$alias} 並未定義，請重新傳入。");
    }

    public static function forCacheRepeatOrch($alias): RedisException
    {
        return new self("此編排器編號- {$alias} 已在 Redis 內被初始化，請重新輸入。");
    }

    public static function forCacheFormatError(): RedisException
    {
        return new self("此 Redis 路徑格式不正確，請重新輸入。");
    }

    public static function forCacheOrchestratorNumberNotFound($alias): RedisException
    {
        return new self("Redis 內找不到此編排器編號- {$alias} ，請重新輸入。");
    }

    public static function forCacheInstanceNotFound(): RedisException
    {
        return new self("Redis 尚未被初始化，請先使用 initCacheDriver 方法初始化 Cache 實體。");
    }

    public static function forServerNameNotFound(): RedisException
    {
        return new self("ServerName 尚未被設定，請傳入 serverName 參數。");
    }

    public static function forClassNameNotFound(): RedisException
    {
        return new self("ClassName 尚未被設定，請傳入 className 參數。");
    }
}
