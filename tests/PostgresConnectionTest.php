<?php

use DGarbs51\PostgresPooledMode\PostgresConnection;

it('formats boolean bindings for emulated prepares', function () {
    $connection = new PostgresConnection(new PostgresPooledModeTestPdo, 'laravel', '', [
        'driver' => 'pgsql',
        'options' => [
            PDO::ATTR_EMULATE_PREPARES => true,
        ],
    ]);

    expect($connection->prepareBindings([true, false]))->toBe(['true', 'false']);
});

it('keeps native prepare boolean bindings as integers', function () {
    $connection = new PostgresConnection(new PostgresPooledModeTestPdo, 'laravel', '', [
        'driver' => 'pgsql',
        'options' => [
            PDO::ATTR_EMULATE_PREPARES => false,
        ],
    ]);

    expect($connection->prepareBindings([true, false]))->toBe([1, 0]);
});

it('uses direct config for direct boolean binding mode', function () {
    $connection = new PostgresConnection(new PostgresPooledModeTestPdo, 'laravel', '', [
        'driver' => 'pgsql',
        'options' => [
            PDO::ATTR_EMULATE_PREPARES => true,
        ],
    ]);

    $connection->setDirectPdoConfig([
        'options' => [
            PDO::ATTR_EMULATE_PREPARES => false,
        ],
    ]);

    $connection->setReadWriteType('direct');

    expect($connection->prepareBindings([true, false]))->toBe([1, 0]);
});

class PostgresPooledModeTestPdo extends PDO
{
    public function __construct()
    {
        //
    }
}
