<?php

use DGarbs51\PostgresPooledMode\DatabaseManager;
use DGarbs51\PostgresPooledMode\PostgresConnection;
use Illuminate\Database\Connectors\ConnectorInterface;

it('replaces laravel database manager', function () {
    expect($this->app['db'])->toBeInstanceOf(DatabaseManager::class);
});

it('resolves direct connection variants through the database manager', function () {
    $this->app->bind('db.connector.pgsql', function () {
        return new class implements ConnectorInterface
        {
            public function connect(array $config)
            {
                return new PostgresPooledModeManagerTestPdo($config);
            }
        };
    });

    $connection = $this->app['db']->connection('pgsql::direct');
    $pdo = $connection->getPdo();

    expect($connection)->toBeInstanceOf(PostgresConnection::class)
        ->and($connection->getNameWithReadWriteType())->toBe('pgsql::direct')
        ->and($pdo)->toBeInstanceOf(PostgresPooledModeManagerTestPdo::class)
        ->and($pdo->config['host'])->toBe('direct-host')
        ->and($pdo->config['port'])->toBe('5432');
});

class PostgresPooledModeManagerTestPdo extends PDO
{
    public function __construct(public array $config)
    {
        //
    }
}
