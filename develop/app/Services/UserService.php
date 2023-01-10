<?php

namespace App\Services;

use App\Entities\v2\WalletEntity;
use App\Models\v2\WalletModel;

class UserService
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


    /**
     * Verify user is exist
     *
     * @param integer $user_key
     * @return WalletEntity|null
     */
    public static function verifyUserIsExist($user_key): ?WalletEntity
    {
        $walletModel = new WalletModel();

        $userWalletEntity = $walletModel->find($user_key);

        return $userWalletEntity;
    }
}
