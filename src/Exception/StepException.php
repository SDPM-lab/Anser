<?php

namespace SDPMlab\Anser\Exception;

use SDPMlab\Anser\Exception\AnserException;

class StepException extends AnserException
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

    public static function forUndefinedStepAction($alias): StepException
    {
        return new self("在 Step 中找不到名為 $alias 的 Action 實體");
    }

    public static function forNonStepAction(): StepException
    {
        return new self("Step 必須宣告至少一個 Action 實體。");
    }

    public static function forCallableActionTypeError($alias): StepException
    {
        return new self("Step Action-{$alias} 的 Callable 必須 return 一個 Action 實體。");
    }

    public static function forActionTypeError($alias): StepException
    {
        return new self("Step Action-{$alias} 必須是 Action 實體。");
    }

}
