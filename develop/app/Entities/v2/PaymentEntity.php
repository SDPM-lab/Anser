<?php

namespace App\Entities\v2;

use CodeIgniter\Entity\Entity;

class PaymentEntity extends Entity
{
    /**
     * Payment primary key
     *
     * @var int
     */
    protected $pm_key;

    /**
     * user foreign key
     *
     * @var int
     */
    protected $u_key;

    /**
     * order foreign key
     *
     * @var string
     */
    protected $o_key;

    /**
     * order total price
     *
     * @var int
     */
    protected $total;

    /**
     * payment status
     *
     * @var int
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
        'pm_key' => 'int'
    ];

    protected $dates = [];
}
