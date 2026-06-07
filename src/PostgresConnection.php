<?php

namespace DGarbs51\PostgresPooledMode;

use Closure;
use DateTimeInterface;
use DGarbs51\PostgresPooledMode\Schema\PostgresSchemaState;
use Illuminate\Database\PostgresConnection as BasePostgresConnection;
use Illuminate\Filesystem\Filesystem;
use PDO;

class PostgresConnection extends BasePostgresConnection
{
    /**
     * The active PDO connection used for direct connections.
     *
     * @var PDO|(Closure(): PDO)|null
     */
    protected $directPdo;

    /**
     * The database connection configuration options for direct connections.
     *
     * @var array
     */
    protected $directPdoConfig = [];

    /**
     * Prepare the query bindings for execution.
     *
     * @return array
     */
    public function prepareBindings(array $bindings)
    {
        $grammar = $this->getQueryGrammar();

        foreach ($bindings as $key => $value) {
            if ($value instanceof DateTimeInterface) {
                $bindings[$key] = $value->format($grammar->getDateFormat());
            } elseif (is_bool($value)) {
                $bindings[$key] = $this->usesEmulatedPrepares()
                    ? ($value ? 'true' : 'false')
                    : (int) $value;
            }
        }

        return $bindings;
    }

    /**
     * Determine if the active PDO configuration uses emulated prepares.
     *
     * @return bool
     */
    protected function usesEmulatedPrepares()
    {
        // Binding preparation runs after query routing has selected the PDO variant.
        $config = match ($this->latestReadWriteTypeUsed()) {
            'read' => $this->readPdoConfig,
            'direct' => $this->directPdoConfig,
            default => $this->config,
        };

        return (bool) ($config['options'][PDO::ATTR_EMULATE_PREPARES] ?? false);
    }

    /**
     * Disconnect from the underlying PDO connection.
     *
     * @return void
     */
    public function disconnect()
    {
        parent::disconnect();

        $this->setDirectPdo(null);
    }

    /**
     * Get the current PDO connection used for direct connections.
     *
     * @return PDO
     */
    public function getDirectPdo()
    {
        $this->latestPdoTypeRetrieved = 'direct';

        if ($this->directPdo instanceof Closure) {
            return $this->directPdo = call_user_func($this->directPdo);
        }

        return $this->directPdo ?: $this->getPdo();
    }

    /**
     * Get the current direct PDO connection parameter without executing any reconnect logic.
     *
     * @return PDO|Closure|null
     */
    public function getRawDirectPdo()
    {
        return $this->directPdo;
    }

    /**
     * Set the PDO connection used for direct connections.
     *
     * @param  PDO|Closure|null  $pdo
     * @return $this
     */
    public function setDirectPdo($pdo)
    {
        $this->directPdo = $pdo;

        return $this;
    }

    /**
     * Set the direct PDO connection configuration.
     *
     * @return $this
     */
    public function setDirectPdoConfig(array $config)
    {
        $this->directPdoConfig = $config;

        return $this;
    }

    /**
     * Get the direct PDO connection configuration.
     *
     * @return array
     */
    public function getDirectConfig()
    {
        return $this->directPdoConfig;
    }

    /**
     * Determine if this connection has a direct PDO connection configured.
     *
     * @return bool
     */
    public function usesDirectConnection()
    {
        return ! empty($this->directPdoConfig);
    }

    /**
     * Get the basic connection information as an array for debugging.
     *
     * @return array
     */
    protected function getConnectionDetails()
    {
        $config = match ($this->latestReadWriteTypeUsed()) {
            'read' => $this->readPdoConfig,
            'direct' => $this->directPdoConfig,
            default => $this->config,
        };

        return [
            'driver' => $this->getDriverName(),
            'name' => $this->getNameWithReadWriteType(),
            'host' => $config['host'] ?? null,
            'port' => $config['port'] ?? null,
            'database' => $config['database'] ?? null,
            'unix_socket' => $config['unix_socket'] ?? null,
        ];
    }

    /**
     * Get the schema state for the connection.
     *
     * @return PostgresSchemaState
     */
    public function getSchemaState(?Filesystem $files = null, ?callable $processFactory = null)
    {
        return new PostgresSchemaState($this, $files, $processFactory);
    }
}
