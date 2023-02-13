<?php

namespace SDPMlab\Anser\Exception;

use SDPMlab\Anser\Exception\AnserException;

class OrchestratorException extends AnserException
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

    public static function forAliasRepeat($alias): OrchestratorException
    {
        return new self("別名「{$alias}」已經被其他 Step 的 Action 使用了。");
    }

    public static function forActionNotFound($alias): OrchestratorException
    {
        return new self("別名「{$alias}」的 Action 並不存在於任何的 Step 中。");
    }

    public static function forCacheOrchestratorNotDefine(): OrchestratorException
    {
        return new self("快取編排器索引尚未被定義，請先使用 setCacheOrchestratorKey() 方法定義編排器索引。");
    }

    public static function forStepNotFoundInSteps($index): OrchestratorException
    {
        return new self("第 {$index} 個 Step 並不存在。");
    }
}
