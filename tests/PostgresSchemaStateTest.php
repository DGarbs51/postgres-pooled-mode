<?php

use DGarbs51\PostgresPooledMode\PostgresConnection;
use DGarbs51\PostgresPooledMode\Schema\PostgresSchemaState;

it('uses direct configuration for schema dump variables', function () {
    $connection = new PostgresConnection(new PostgresSchemaStateTestPdo);
    $connection->setDirectPdoConfig([
        'host' => ['direct-host', 'direct-host-2'],
        'port' => '5432',
        'username' => 'direct_user',
        'password' => 'direct_secret',
        'database' => 'direct_database',
        'sslmode' => 'require',
    ]);

    $variables = (new ReflectionMethod(PostgresSchemaState::class, 'baseVariables'))->invoke(new PostgresSchemaState($connection), [
        'host' => 'pooler-host',
        'port' => '6432',
        'username' => 'root',
        'password' => 'secret',
        'database' => 'laravel',
        'sslmode' => 'prefer',
    ]);

    expect($variables)->toBe([
        'LARAVEL_LOAD_HOST' => 'direct-host',
        'LARAVEL_LOAD_PORT' => '5432',
        'LARAVEL_LOAD_USER' => 'direct_user',
        'PGPASSWORD' => 'direct_secret',
        'PGSSLMODE' => 'require',
        'LARAVEL_LOAD_DATABASE' => 'direct_database',
    ]);
});

it('does not export empty sslmode for schema dump variables', function () {
    $connection = new PostgresConnection(new PostgresSchemaStateTestPdo);

    $variables = (new ReflectionMethod(PostgresSchemaState::class, 'baseVariables'))->invoke(new PostgresSchemaState($connection), [
        'host' => 'pooler-host',
        'port' => '6432',
        'username' => 'root',
        'password' => 'secret',
        'database' => 'laravel',
    ]);

    expect($variables)->toBe([
        'LARAVEL_LOAD_HOST' => 'pooler-host',
        'LARAVEL_LOAD_PORT' => '6432',
        'LARAVEL_LOAD_USER' => 'root',
        'PGPASSWORD' => 'secret',
        'LARAVEL_LOAD_DATABASE' => 'laravel',
    ]);
});

class PostgresSchemaStateTestPdo extends PDO
{
    public function __construct()
    {
        //
    }
}
