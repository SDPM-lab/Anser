<?php

namespace App\Entities\v2;

use CodeIgniter\Entity\Entity;

class InventoryHistoryEntity extends Entity
{
    /**
     * inventory history key
     *
     * @var int
     */
    protected $ih_key;

    /**
     * history type
     *
     * @var string
     */
    protected $type;

    /**
     * product key
     *
     * @var int
     */
    protected $p_key;

    /**
     * This transaction increase or reduce amount.
     *
     * @var int
     */
    protected $amount;

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
        'ih_key' => 'int'
    ];

    protected $dates = [];
}
