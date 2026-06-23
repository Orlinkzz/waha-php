<?php

namespace Orlinkzz\Waha\Laravel;

use Illuminate\Support\ServiceProvider;
use Orlinkzz\Waha\WahaClient;
use Orlinkzz\Waha\WahaConfig;

class WahaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/waha.php', 'waha');

        $this->app->singleton(WahaConfig::class, function ($app) {
            $config = $app['config']['waha'];

            // Merge database config from Laravel's database config if not explicitly set
            if (!isset($config['database']) && $app['config']->has('database.default')) {
                $dbConnection = $app['config']['database.default'];
                $dbConfig = $app['config']['database.connections.' . $dbConnection] ?? [];

                if (!empty($dbConfig)) {
                    $config['database'] = [
                        'driver' => $dbConfig['driver'] === 'pgsql' ? 'pgsql' : 'mysql',
                        'host' => $dbConfig['host'] ?? '127.0.0.1',
                        'port' => $dbConfig['port'] ?? ($dbConfig['driver'] === 'pgsql' ? '5432' : '3306'),
                        'database' => $dbConfig['database'] ?? 'waha',
                        'username' => $dbConfig['username'] ?? 'root',
                        'password' => $dbConfig['password'] ?? '',
                        'charset' => $dbConfig['charset'] ?? 'utf8mb4',
                    ];
                }
            }

            return WahaConfig::fromArray($config);
        });

        $this->app->singleton(WahaClient::class, function ($app) {
            return new WahaClient($app->make(WahaConfig::class));
        });

        // Alias for short access: app('waha')
        $this->app->alias(WahaClient::class, 'waha');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/waha.php' => config_path('waha.php'),
            ], 'waha-config');

            // Register migration command
            $this->commands([
                \Orlinkzz\Waha\Laravel\Console\Commands\WahaMigrateCommand::class,
            ]);
        }
    }
}
