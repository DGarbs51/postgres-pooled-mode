<?php

namespace DGarbs51\PostgresPooledMode\Connectors;

use DGarbs51\PostgresPooledMode\PostgresConnection;
use Illuminate\Database\Connection;
use Illuminate\Database\Connectors\ConnectionFactory as BaseConnectionFactory;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use PDO;

class ConnectionFactory extends BaseConnectionFactory
{
    /**
     * Establish a PDO connection based on the configuration.
     *
     * @param  string|null  $name
     * @return Connection
     */
    public function make(array $config, $name = null)
    {
        $config = $this->parseConfig($config, $name);
        $config = $this->applyPooledPostgresOptions($config);

        if (isset($config['read'])) {
            return $this->createReadWriteConnection($config);
        }

        return $this->createSingleConnection($config);
    }

    /**
     * Create a single database connection instance.
     *
     * @return Connection
     */
    protected function createSingleConnection(array $config)
    {
        $connection = parent::createSingleConnection($config);

        if ($this->hasDirectConnection($config) && method_exists($connection, 'setDirectPdo')) {
            $connection->setDirectPdo($this->createDirectPdo($config))
                ->setDirectPdoConfig($this->getDirectConfig($config));
        }

        return $connection;
    }

    /**
     * Create a read / write database connection instance.
     *
     * @return Connection
     */
    protected function createReadWriteConnection(array $config)
    {
        $connection = parent::createReadWriteConnection($config);

        if ($this->hasDirectConnection($config) && method_exists($connection, 'setDirectPdo')) {
            $connection->setDirectPdo($this->createDirectPdo($config))
                ->setDirectPdoConfig($this->getDirectConfig($config));
        }

        return $connection;
    }

    /**
     * Create a new PDO instance for direct connections.
     *
     * @return \Closure
     */
    protected function createDirectPdo(array $config)
    {
        return $this->createPdoResolver($this->getDirectConfig($config));
    }

    /**
     * Get the direct configuration for a connection.
     *
     * @return array
     */
    protected function getDirectConfig(array $config)
    {
        return $this->mergeDirectConfig(
            $config, $this->getReadWriteConfig($config, 'direct')
        );
    }

    /**
     * Merge a configuration for a direct connection.
     *
     * @return array
     */
    protected function mergeDirectConfig(array $config, array $merge)
    {
        $direct = Arr::except(array_merge($config, $merge), [
            'read', 'write', 'direct', 'pooled', 'connect_via_database', 'connect_via_port',
        ]);

        if (! isset($direct['options']) || ! is_array($direct['options'])) {
            $direct['options'] = [];
        }

        $directEmulatePreparesConfigured = isset($merge['options']) &&
            is_array($merge['options']) &&
            array_key_exists(PDO::ATTR_EMULATE_PREPARES, $merge['options']);

        if (! $directEmulatePreparesConfigured) {
            $direct['options'][PDO::ATTR_EMULATE_PREPARES] = false;
        }

        return $direct;
    }

    /**
     * Apply transaction-pooler options to PostgreSQL connections.
     *
     * @return array
     */
    protected function applyPooledPostgresOptions(array $config)
    {
        if (($config['driver'] ?? null) !== 'pgsql') {
            return $config;
        }

        $hasDirectConnection = ! empty($config['direct']);

        if (! $hasDirectConnection && ($config['pooled'] ?? false) !== true) {
            return $config;
        }

        if ($hasDirectConnection) {
            $config['pooled'] = true;
        }

        if (! $hasDirectConnection && ($config['pooled'] ?? false) === true) {
            trigger_error(
                "Database connection [{$config['name']}] sets 'pooled' => true without a 'direct' endpoint; migrations and DDL will still traverse the transaction pooler.",
                E_USER_WARNING
            );
        }

        $config = $this->withEmulatedPrepares($config);

        foreach (['read', 'write'] as $type) {
            if (! isset($config[$type])) {
                continue;
            }

            if (isset($config[$type][0])) {
                foreach ($config[$type] as $index => $connection) {
                    if (isset($connection['options'])) {
                        $config[$type][$index] = $this->withEmulatedPrepares($connection);
                    }
                }
            } elseif (isset($config[$type]['options'])) {
                $config[$type] = $this->withEmulatedPrepares($config[$type]);
            }
        }

        return $config;
    }

    /**
     * Stamp emulated prepares onto a connection configuration when not explicit.
     *
     * @return array
     */
    protected function withEmulatedPrepares(array $config)
    {
        if (! isset($config['options']) || ! is_array($config['options'])) {
            $config['options'] = [];
        }

        if (! array_key_exists(PDO::ATTR_EMULATE_PREPARES, $config['options'] ?? [])) {
            $config['options'][PDO::ATTR_EMULATE_PREPARES] = true;
        }

        return $config;
    }

    /**
     * Determine if the configuration has a direct PostgreSQL connection.
     *
     * @return bool
     */
    protected function hasDirectConnection(array $config)
    {
        return ($config['driver'] ?? null) === 'pgsql' && ! empty($config['direct']);
    }

    /**
     * Create a new connection instance.
     *
     * @param  string  $driver
     * @param  PDO|\Closure  $connection
     * @param  string  $database
     * @param  string  $prefix
     * @return Connection
     *
     * @throws InvalidArgumentException
     */
    protected function createConnection($driver, $connection, $database, $prefix = '', array $config = [])
    {
        if ($resolver = Connection::getResolver($driver)) {
            return $resolver($connection, $database, $prefix, $config);
        }

        return match ($driver) {
            'pgsql' => new PostgresConnection($connection, $database, $prefix, $config),
            default => parent::createConnection($driver, $connection, $database, $prefix, $config),
        };
    }
}
