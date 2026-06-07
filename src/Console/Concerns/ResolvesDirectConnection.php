<?php

namespace DGarbs51\PostgresPooledMode\Console\Concerns;

use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Support\Str;

trait ResolvesDirectConnection
{
    /**
     * Resolve a database connection, preferring the direct variant when configured.
     *
     * @param  ConnectionResolverInterface  $connections
     * @param  string|null  $database
     * @return Connection
     */
    protected function resolveConnection($connections, $database)
    {
        $name = $database ?: $connections->getDefaultConnection();
        $connection = $connections->connection($name);

        return method_exists($connection, 'usesDirectConnection') &&
            $connection->usesDirectConnection() &&
            ! Str::endsWith($name, ['::read', '::write', '::direct'])
                ? $connections->connection($name.'::direct')
                : $connection;
    }
}
