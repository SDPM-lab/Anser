<?php

namespace App\Entities\v2;

use CodeIgniter\Entity\Entity;

class OrderHistoryEntity extends Entity
{
    /**
     * order history key
     *
     * @var int
     */
    protected $oh_key;

    /**
     * history type
     *
     * @var string
     */
    protected $type;

    /**
     * order key
     *
     * @var string
     */
    protected $o_key;

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
        'oh_key' => 'int'
    ];

    protected $dates = [];
}
