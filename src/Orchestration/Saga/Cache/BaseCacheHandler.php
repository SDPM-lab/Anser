<?php

namespace SDPMlab\Anser\Orchestration\Saga\Cache;

use Exception;

abstract class BaseCacheHandler implements CacheHandlerInterface
{
    /**
     * Undocumented variable
     *
     * @var string
     */
    protected $handler;

    /**
     * Set cache driver.
     *
     */
    protected $driver = '';

    /**
     * Default config
     *
     * @var array
     */
    protected $config = [
        'host'     => '127.0.0.1',
        'password' => null,
        'port'     => 6379,
        'timeout'  => 0,
        'database' => 0,
    ];

    /**
     * The option of connection.
     *
     * @var array
     */
    protected $option = [];

    public function __construct(string $driver, ?array $config, ?array $option)
    {
        if ($config !== null) {
            $this->config = $config;
        }

        if ($option !== null) {
            $this->option = $option;
        }

        $cacheDriverName = ucfirst(strtolower($driver));
        $cacheDriverPath = __DIR__ . '/' . $cacheDriverName . '/' . $cacheDriverName . 'Handler.php';

        if (file_exists($cacheDriverPath)) {
            require_once $cacheDriverPath;

            $this->driver = new $cacheDriverPath($this);
        } else {
            throw new Exception('建構 ' . $className . ' 時發生錯誤，請重新再試');
        }
    }

    public function serializeOrchestrator(array $orchestratorData): string
    {
        return "";
    }

    public function unserializeOrchestrator(string $orchestratorNumber): array
    {
        return [];
    }
}
