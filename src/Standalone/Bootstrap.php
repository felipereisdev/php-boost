<?php

namespace FelipeReisDev\PhpBoost\Standalone;

use FelipeReisDev\PhpBoost\Core\Mcp\Server;
use FelipeReisDev\PhpBoost\Core\Mcp\Transport\StdioTransport;
use FelipeReisDev\PhpBoost\Core\Support\ToolRegistrar;

class Bootstrap
{
    private $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->getDefaultConfig(), $config);
    }

    private function getDefaultConfig()
    {
        return [
            'database' => [
                'driver' => 'mysql',
                'host' => 'localhost',
                'database' => '',
                'username' => 'root',
                'password' => '',
                'port' => 3306,
            ],
            'log_path' => __DIR__ . '/../../storage/logs/app.log',
        ];
    }

    public function createServer()
    {
        $server = new Server(new StdioTransport(), $this->config);

        $registry = $server->getToolRegistry();
        ToolRegistrar::registerCoreTools($registry, $this->config);

        return $server;
    }

    public function start()
    {
        $server = $this->createServer();
        $server->start();
    }

    public static function fromEnv($basePath = null)
    {
        $resolvedBasePath = rtrim(
            $basePath ?: (getenv('PHP_BOOST_PROJECT_PATH') ?: getcwd()),
            '/'
        );

        $defaultLogPath = $resolvedBasePath . '/storage/logs/laravel.log';
        if (!file_exists($defaultLogPath)) {
            $defaultLogPath = $resolvedBasePath . '/storage/logs/app.log';
        }

        $config = [
            'base_path' => $resolvedBasePath,
            'database' => [
                'driver' => getenv('DB_CONNECTION') ?: 'mysql',
                'host' => getenv('DB_HOST') ?: 'localhost',
                'database' => getenv('DB_DATABASE') ?: '',
                'username' => getenv('DB_USERNAME') ?: 'root',
                'password' => getenv('DB_PASSWORD') ?: '',
                'port' => getenv('DB_PORT') ?: 3306,
            ],
            'log_path' => getenv('LOG_PATH') ?: $defaultLogPath,
        ];

        return new self($config);
    }
}
