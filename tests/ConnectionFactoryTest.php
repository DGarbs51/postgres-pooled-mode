<?php

use DGarbs51\PostgresPooledMode\Connectors\ConnectionFactory;
use DGarbs51\PostgresPooledMode\PostgresConnection;

it('creates a direct aware postgres connection without opening pdo connections', function () {
    $connection = (new ConnectionFactory($this->app))->make([
        'driver' => 'pgsql',
        'host' => 'pooler-host',
        'port' => '6432',
        'database' => 'laravel',
        'username' => 'root',
        'password' => '',
        'prefix' => '',
        'connect_via_database' => 'pooler_database',
        'connect_via_port' => '6432',
        'direct' => [
            'host' => 'direct-host',
            'port' => '5432',
            'sslmode' => 'require',
        ],
    ], 'pgsql');

    expect($connection)->toBeInstanceOf(PostgresConnection::class)
        ->and($connection->usesDirectConnection())->toBeTrue()
        ->and($connection->getConfig('pooled'))->toBeTrue()
        ->and($connection->getConfig('options')[PDO::ATTR_EMULATE_PREPARES])->toBeTrue();

    $directConfig = $connection->getDirectConfig();

    expect($directConfig['host'])->toBe('direct-host')
        ->and($directConfig['port'])->toBe('5432')
        ->and($directConfig['sslmode'])->toBe('require')
        ->and($directConfig['database'])->toBe('laravel')
        ->and($directConfig['options'][PDO::ATTR_EMULATE_PREPARES])->toBeFalse()
        ->and($directConfig)->not->toHaveKey('connect_via_database')
        ->and($directConfig)->not->toHaveKey('connect_via_port');
});

it('preserves explicit emulated prepares options', function (?bool $baseOption, ?bool $directOption, bool $expectedPooledOption, bool $expectedDirectOption) {
    $config = [
        'driver' => 'pgsql',
        'host' => 'pooler-host',
        'database' => 'laravel',
        'username' => 'root',
        'password' => '',
        'prefix' => '',
        'direct' => [
            'host' => 'direct-host',
        ],
    ];

    if (! is_null($baseOption)) {
        $config['options'][PDO::ATTR_EMULATE_PREPARES] = $baseOption;
    }

    if (! is_null($directOption)) {
        $config['direct']['options'][PDO::ATTR_EMULATE_PREPARES] = $directOption;
    }

    $connection = (new ConnectionFactory($this->app))->make($config, 'pgsql');

    expect($connection->getConfig('options')[PDO::ATTR_EMULATE_PREPARES])->toBe($expectedPooledOption)
        ->and($connection->getDirectConfig()['options'][PDO::ATTR_EMULATE_PREPARES])->toBe($expectedDirectOption);
})->with([
    'base missing, direct missing' => [null, null, true, false],
    'base missing, direct true' => [null, true, true, true],
    'base missing, direct false' => [null, false, true, false],
    'base true, direct missing' => [true, null, true, false],
    'base true, direct true' => [true, true, true, true],
    'base true, direct false' => [true, false, true, false],
    'base false, direct missing' => [false, null, false, false],
    'base false, direct true' => [false, true, false, true],
    'base false, direct false' => [false, false, false, false],
]);
