<?php

namespace App\Entities\v2;

use CodeIgniter\Entity\Entity;

class ProductEntity extends Entity
{
    /**
     * product primary key
     *
     * @var int
     */
    protected $p_key;

    /**
     * product name
     *
     * @var string
     */
    protected $name;

    /**
     * product price
     *
     * @var int
     */
    protected $price;

    /**
     * product price
     *
     * @var int
     */
    protected $amount;

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
        'p_key' => 'int'
    ];

    protected $dates = [];
}
