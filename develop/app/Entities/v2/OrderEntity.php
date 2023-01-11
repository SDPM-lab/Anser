<?php

namespace App\Entities\v2;

use CodeIgniter\Entity\Entity;

class OrderEntity extends Entity
{
    /**
     * order key
     *
     * @var string
     */
    protected $o_key;

    /**
     * user key
     *
     * @var int
     */
    protected $u_key;

    /**
     * product key
     *
     * @var int
     */
    protected $p_key;

    /**
     * order amount(數量)
     *
     * @var int
     */
    protected $amount;

    /**
     * order status
     *
     * @var string
     */
    protected $status;

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
        'o_key' => 'string'
    ];

    protected $dates = [];
}
