<?php

namespace SDPMlab\Anser\Exception;

use SDPMlab\Anser\Exception\AnserException;

class RedisException extends AnserException
{

    /**
     * 初始化　SimpleServiceException
     *
     * @param string $message 錯誤訊息
     */
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function forCacheDriverNotFound($alias): RedisException
    {
        return new self("傳入快取驅動程式名稱-{$alias} 並未定義，請重新傳入。");
    }

}
