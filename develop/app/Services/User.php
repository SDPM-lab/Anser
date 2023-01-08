<?php

namespace App\Services;

class User
{
    /**
     * 使用者主鍵
     *
     * @var null|int
     */
    private static $u_key;

    /**
     * 取得使用者 key
     *
     * @return integer|null
     */
    public static function getUserKey(): ?int
    {
        return self::$u_key;
    }

    /**
     * 設定使用者 key
     *
     * @param int $user_key
     * @return void
     */
    public static function setUserKey(string $user_key)
    {
        self::$u_key = $user_key;
    }
}
