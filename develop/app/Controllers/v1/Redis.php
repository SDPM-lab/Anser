<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use Predis\Client;

class Redis extends BaseController
{
    /**
     * Redis client;
     *
     * @var Client
     */
    protected $client;

    public function __construct()
    {
        $this->client = new Client([
            'scheme' => 'tcp',
            'host'   => 'service_redis',
            'port'   => '6379'
        ]);
    }

    public function index()
    {
        $this->client->set('foo', 'bar');
        dd($this->client->get('foo'));
    }
}
