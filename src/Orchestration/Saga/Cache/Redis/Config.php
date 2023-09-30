<?php

namespace SDPMlab\Anser\Orchestration\Saga\Cache\Redis;

class config
{
    /**
     * The scheme of Redis.
     *
     * @var string
     */
    public $scheme = 'tcp';

    /**
     * The host of Redis.
     *
     * @var string
     */
    public $host = '127.0.0.1';

    /**
     * The port of Redis.
     *
     * @var integer
     */
    public $port = 6379;

    /**
     * The timeout of Redis.
     *
     * @var integer|float
     */
    public $timeout = 0;

    /**
     * The db of Redis.
     *
     * @var integer
     */
    public $db = 0;

    /**
     * The serverName as the hashmap key.
     * You can set this value to identify the server.
     *
     * @var string
     */
    public $serverName = 'AnserServer';

    public function __construct(
        string $scheme = 'tcp',
        string $host = 'localhost',
        int $port = 6379,
        int $timeout = 0,
        int $db = 0,
        string $serverName = 'AnserServer'
    ) {
        $this->scheme  = $scheme;
        $this->host    = $host;
        $this->port    = $port;
        $this->timeout = $timeout;
        $this->db      = $db;
        $this->serverName = $serverName;
    }

    public function __sleep()
    {
        return ['scheme', 'host', 'port', 'timeout', 'db', 'serverName'];
    }
}