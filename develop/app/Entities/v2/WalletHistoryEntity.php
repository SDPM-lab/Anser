<?php

namespace App\Entities\v2;

use CodeIgniter\Entity\Entity;

class WalletHistoryEntity extends Entity
{
    /**
     * wallet history key
     *
     * @var int
     */
    protected $wh_key;

    /**
     * history type
     *
     * @var string
     */
    protected $type;

    /**
     * user key
     *
     * @var int
     */
    protected $u_key;

    /**
     * This transaction increase or reduce balance.
     *
     * @var int
     */
    protected $balance;

    /**
     * orch key
     *
     * @var int
     */
    protected $orch_key;

    /**
     * 建立時間
     *
     * @var string
     */
    protected $createdAt;

    /**
     * 最後更新時間
     *
     * @var string
     */
    protected $updatedAt;

    /**
     * 刪除時間
     *
     * @var string
     */
    protected $deletedAt;

    protected $datamap = [
        'createdAt' => 'created_at',
        'updatedAt' => 'updated_at',
        'deletedAt' => 'deleted_at'
    ];

    protected $casts = [
        'wh_key' => 'int'
    ];

    protected $dates = [];
}
