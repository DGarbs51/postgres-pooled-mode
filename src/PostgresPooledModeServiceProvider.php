<?php

namespace DGarbs51\PostgresPooledMode;

use DGarbs51\PostgresPooledMode\Connectors\ConnectionFactory;
use DGarbs51\PostgresPooledMode\Console\DbCommand;
use DGarbs51\PostgresPooledMode\Console\ShowCommand;
use DGarbs51\PostgresPooledMode\Console\TableCommand;
use DGarbs51\PostgresPooledMode\Console\WipeCommand;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\ServiceProvider;

class PostgresPooledModeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/postgres-pooled-mode.php', 'postgres-pooled-mode');

        if (! $this->enabled()) {
            return;
        }

        $this->registerDatabaseServices();
    }

    public function boot(): void
    {
        if (! $this->enabled()) {
            return;
        }

        Model::setConnectionResolver($this->app['db']);

        if (config('postgres-pooled-mode.migrations_use_direct', true)) {
            $this->routeMigrationsThroughDirectConnections();
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/postgres-pooled-mode.php' => config_path('postgres-pooled-mode.php'),
            ], 'postgres-pooled-mode-config');

            if (config('postgres-pooled-mode.replace_console_commands', true)) {
                $this->commands([
                    DbCommand::class,
                    ShowCommand::class,
                    TableCommand::class,
                    WipeCommand::class,
                ]);
            }
        }
    }

    protected function registerDatabaseServices(): void
    {
        $this->app->forgetInstance('db.factory');
        $this->app->forgetInstance('db');

        $this->app->singleton('db.factory', function ($app) {
            return new ConnectionFactory($app);
        });

        $this->app->singleton('db', function ($app) {
            return new DatabaseManager($app, $app['db.factory']);
        });

        $this->app->bind('db.connection', function ($app) {
            return $app['db']->connection();
        });

        $this->app->bind('db.schema', function ($app) {
            return $app['db']->connection()->getSchemaBuilder();
        });
    }

    protected function routeMigrationsThroughDirectConnections(): void
    {
        Migrator::resolveConnectionsUsing(function ($resolver, $connection) {
            $name = $connection ?: $resolver->getDefaultConnection();

            if (str_ends_with($name, '::read') ||
                str_ends_with($name, '::write') ||
                str_ends_with($name, '::direct')) {
                return $resolver->connection($name);
            }

            $resolved = $resolver->connection($name);

            return method_exists($resolved, 'usesDirectConnection') && $resolved->usesDirectConnection()
                ? $resolver->connection($name.'::direct')
                : $resolved;
        });
    }

    protected function enabled(): bool
    {
        return (bool) config('postgres-pooled-mode.enabled', true);
    }
}
