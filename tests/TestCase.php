<?php

namespace DGarbs51\PostgresPooledMode\Tests;

use DGarbs51\PostgresPooledMode\PostgresPooledModeServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            PostgresPooledModeServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'pgsql');
        $app['config']->set('database.connections.pgsql', [
            'driver' => 'pgsql',
            'host' => 'pooler-host',
            'port' => '6432',
            'database' => 'laravel',
            'username' => 'root',
            'password' => '',
            'prefix' => '',
            'direct' => [
                'host' => 'direct-host',
                'port' => '5432',
                'sslmode' => 'require',
            ],
        ]);
    }
}
