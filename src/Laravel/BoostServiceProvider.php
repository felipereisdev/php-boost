<?php

namespace FelipeReisDev\PhpBoost\Laravel;

use FelipeReisDev\PhpBoost\Core\Mcp\Server;
use FelipeReisDev\PhpBoost\Core\Mcp\Transport\StdioTransport;
use FelipeReisDev\PhpBoost\Core\Tools\GetConfig;
use FelipeReisDev\PhpBoost\Core\Tools\DatabaseSchema;
use FelipeReisDev\PhpBoost\Core\Tools\DatabaseQuery;
use FelipeReisDev\PhpBoost\Core\Tools\ReadLogEntries;
use FelipeReisDev\PhpBoost\Laravel\Tools\ListRoutes;
use Illuminate\Support\ServiceProvider;

class BoostServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/boost.php',
            'boost'
        );
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/boost.php' => config_path('boost.php'),
            ], 'boost-config');

            $this->commands([
                Console\StartCommand::class,
                Console\InstallCommand::class,
                Console\ValidateCommand::class,
                Console\MigrateGuideCommand::class,
                Console\HealthCommand::class,
                Console\SnippetCommand::class,
                Console\ProfileCommand::class,
                Console\DocsCommand::class,
                Console\AnalyzeCommand::class,
            ]);
        }

        $this->app->singleton(Server::class, function ($app) {
            $config = [
                'database' => [
                    'driver' => config('database.default'),
                    'host' => config('database.connections.' . config('database.default') . '.host'),
                    'database' => config('database.connections.' . config('database.default') . '.database'),
                    'username' => config('database.connections.' . config('database.default') . '.username'),
                    'password' => config('database.connections.' . config('database.default') . '.password'),
                    'port' => config('database.connections.' . config('database.default') . '.port', 3306),
                ],
                'log_path' => storage_path('logs/laravel.log'),
            ];

            $server = new Server(new StdioTransport(), $config);
            
            $registry = $server->getToolRegistry();
            $registry->register(new GetConfig($config));
            $registry->register(new DatabaseSchema($config));
            $registry->register(new DatabaseQuery($config));
            $registry->register(new ReadLogEntries($config));
            $registry->register(new ListRoutes($config));

            return $server;
        });
    }
}
