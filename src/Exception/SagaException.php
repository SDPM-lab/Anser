<?php

namespace SDPMlab\Anser\Exception;

use SDPMlab\Anser\Exception\AnserException;

class SagaException extends AnserException
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

    public static function forSimpleSagaNotFound($alias): SagaException
    {
        return new self("找不到 {$alias} 的 SimpleSaga 類別。");
    }

    public static function forCompensationMethodNotFound($alias): SagaException
    {
        return new self("{$alias} 補償方法尚未被定義於 SimpleSaga 內。");
    }

    public static function forCompensationMethodNotFoundForStep($alias): SagaException
    {
        return new self("第 {$alias} 步驟需補償但尚未定義補償方法。");
    }
}
