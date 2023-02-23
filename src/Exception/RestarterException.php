<?php

namespace SDPMlab\Anser\Exception;

use SDPMlab\Anser\Exception\AnserException;

class RestarterException extends AnserException
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

    public static function forOrchestratorNumberNotFound(): RestarterException
    {
        return new self("編排器編號未傳入，請傳入參數。");
    }

    public static function forCacheInstanceNotFound(): RestarterException
    {
        return new self("快取實體尚未被建構，請先呼叫 setRestarterCacheInstance 方法建構。");
    }

    public static function forClassNameIsNull(): RestarterException
    {
        return new self("未傳入欲重啟的類別名稱，請將傳入 className 參數。");
    }

    public static function forServerNameIsNull(): RestarterException
    {
        return new self("未傳入欲重啟的伺服器名稱，請傳入或在 .env 檔案內設定 serverName 參數。");
    }
}
