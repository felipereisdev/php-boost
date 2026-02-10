<?php

namespace FelipeReisDev\PhpBoost\Lumen;

use FelipeReisDev\PhpBoost\Core\Mcp\Server;
use FelipeReisDev\PhpBoost\Core\Mcp\Transport\StdioTransport;
use FelipeReisDev\PhpBoost\Core\Support\ToolRegistrar;
use Illuminate\Support\ServiceProvider;

class BoostServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(Server::class, function ($app) {
            $config = [
                'database' => [
                    'driver' => env('DB_CONNECTION', 'mysql'),
                    'host' => env('DB_HOST', 'localhost'),
                    'database' => env('DB_DATABASE', ''),
                    'username' => env('DB_USERNAME', ''),
                    'password' => env('DB_PASSWORD', ''),
                    'port' => env('DB_PORT', 3306),
                ],
                'log_path' => storage_path('logs/lumen.log'),
            ];

            $server = new Server(new StdioTransport(), $config);

            $registry = $server->getToolRegistry();
            ToolRegistrar::registerCoreTools($registry, $config);
            ToolRegistrar::registerLumenTools($registry, $config);

            return $server;
        });
    }

    public function boot()
    {
        $this->app->singleton('command.boost.start', function ($app) {
            return new Console\StartCommand();
        });

        $this->commands(['command.boost.start']);
    }
}
