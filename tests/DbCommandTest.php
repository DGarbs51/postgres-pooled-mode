<?php

use DGarbs51\PostgresPooledMode\Console\DbCommand;

it('defaults db command connections to direct postgres credentials when pooled mode is enabled', function () {
    $this->app['config']->set('database.connections.pgsql', [
        'driver' => 'pgsql',
        'host' => 'pooler-host',
        'port' => '6432',
        'database' => 'laravel',
        'username' => 'pooler-user',
        'password' => 'pooler-password',
        'prefix' => '',
        'pooled' => true,
        'direct' => [
            'host' => ['direct-host', 'direct-host-2'],
            'port' => '5432',
            'username' => 'direct-user',
            'password' => 'direct-password',
            'sslmode' => 'require',
        ],
    ]);

    $command = new PostgresPooledModeDbCommandTestCommand;
    $command->setLaravel($this->app);

    $connection = $command->getConnection();

    expect($connection['host'])->toBe('direct-host')
        ->and($connection['port'])->toBe('5432')
        ->and($connection['username'])->toBe('direct-user')
        ->and($connection['password'])->toBe('direct-password')
        ->and($connection['sslmode'])->toBe('require')
        ->and($connection['database'])->toBe('laravel')
        ->and($connection)->not->toHaveKey('direct')
        ->and($connection)->not->toHaveKey('pooled');
});

class PostgresPooledModeDbCommandTestCommand extends DbCommand
{
    public function argument($key = null)
    {
        return is_null($key) ? [] : null;
    }

    public function option($key = null)
    {
        $options = [
            'read' => false,
            'write' => false,
            'pooled' => false,
        ];

        return is_null($key) ? $options : $options[$key];
    }
}
