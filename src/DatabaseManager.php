<?php

namespace DGarbs51\PostgresPooledMode;

use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager as BaseDatabaseManager;
use Illuminate\Support\Str;

class DatabaseManager extends BaseDatabaseManager
{
    /**
     * Parse the connection into an array of the name and read / write type.
     *
     * @param  string  $name
     * @return array
     */
    protected function parseConnectionName($name)
    {
        return Str::endsWith($name, ['::read', '::write', '::direct'])
            ? explode('::', $name, 2)
            : [$name, null];
    }

    /**
     * Prepare the read / write mode for database connection instance.
     *
     * @param  string|null  $type
     * @return Connection
     */
    protected function setPdoForType(Connection $connection, $type = null)
    {
        if ($type === 'direct' && method_exists($connection, 'getDirectPdo')) {
            return $connection->setPdo($connection->getDirectPdo())
                ->setReadPdo($connection->getDirectPdo());
        }

        return parent::setPdoForType($connection, $type);
    }

    /**
     * Refresh the PDO connections on a given connection.
     *
     * @param  string  $name
     * @return Connection
     */
    protected function refreshPdoConnections($name)
    {
        [$database, $type] = $this->parseConnectionName($name);

        $fresh = $this->configure(
            $this->makeConnection($database), $type
        );

        $connection = $this->connections[$name]
            ->setPdo($fresh->getRawPdo())
            ->setReadPdo($fresh->getRawReadPdo());

        if (method_exists($connection, 'setDirectPdo') && method_exists($fresh, 'getRawDirectPdo')) {
            $connection->setDirectPdo($fresh->getRawDirectPdo());
        }

        return $connection;
    }
}
