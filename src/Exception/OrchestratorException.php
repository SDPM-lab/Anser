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

    /**
     * @deprecated v0.1
     *
     * @return OrchestratorException
     */
    public static function forCacheOrchestratorNotDefine(): OrchestratorException
    {
        return new self("快取編排器索引尚未被定義，請先使用 setCacheOrchestratorKey() 方法定義編排器索引。");
    }

    public static function forStepNotFoundInSteps($index): OrchestratorException
    {
        return new self("第 {$index} 個 Step 並不存在。");
    }

    public static function forSagaInstanceNotFound(): OrchestratorException
    {
        return new self("Saga 實體並不存在。");
    }

    public static function forServerNameNotFound(): OrchestratorException
    {
        return new self("ServerName 尚未設定，請使用 setServerName() 方法或在 .env 檔案內進行 serverName 設定");
    }

    public static function forSagaInstanceNotFoundInCache(): OrchestratorException
    {
        return new self("在重啟邏輯需先設定 Saga 補償邏輯。");
    }

    public static function forStepActionMeaningDataIsNull($alias): OrchestratorException
    {
        return new self("此步驟- {$alias} 的 Action 的 meaningData 為 null，請確認 setMeaningData 方法或是此步驟尚未被處理。");
    }
}
