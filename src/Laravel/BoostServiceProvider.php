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
    private function resolveDatabaseHost(array $connection): string
    {
        $host = $connection['host'] ?? null;
        if (is_array($host)) {
            $host = reset($host);
        }

        if (!empty($host)) {
            return (string) $host;
        }

        $readHost = $connection['read']['host'] ?? null;
        if (is_array($readHost)) {
            $readHost = reset($readHost);
        }

        if (!empty($readHost)) {
            return (string) $readHost;
        }

        $writeHost = $connection['write']['host'] ?? null;
        if (is_array($writeHost)) {
            $writeHost = reset($writeHost);
        }

        if (!empty($writeHost)) {
            return (string) $writeHost;
        }

        return 'localhost';
    }

    private function resolveDatabasePort(string $driver, array $connection): int
    {
        if (!empty($connection['port'])) {
            return (int) $connection['port'];
        }

        switch ($driver) {
            case 'pgsql':
                return 5432;
            case 'sqlsrv':
                return 1433;
            default:
                return 3306;
        }
    }

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
            $defaultConnection = (string) config('database.default');
            $connection = config('database.connections.' . $defaultConnection, []);
            $driver = (string) ($connection['driver'] ?? $defaultConnection);

            $config = [
                'database' => [
                    'driver' => $driver,
                    'host' => $this->resolveDatabaseHost($connection),
                    'database' => $connection['database'] ?? null,
                    'username' => $connection['username'] ?? null,
                    'password' => $connection['password'] ?? null,
                    'port' => $this->resolveDatabasePort($driver, $connection),
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
