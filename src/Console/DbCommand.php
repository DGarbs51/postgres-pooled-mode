<?php

namespace DGarbs51\PostgresPooledMode\Console;

use Illuminate\Database\Console\DbCommand as BaseDbCommand;
use Illuminate\Support\Arr;
use Illuminate\Support\ConfigurationUrlParser;
use UnexpectedValueException;

class DbCommand extends BaseDbCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db {connection? : The database connection that should be used}
               {--read : Connect to the read connection}
               {--write : Connect to the write connection}
               {--pooled : Connect to the pooled connection}';

    /**
     * Get the database connection configuration.
     *
     * @return array
     *
     * @throws UnexpectedValueException
     */
    public function getConnection()
    {
        $connection = $this->laravel['config']['database.connections.'.
            (($db = $this->argument('connection')) ?? $this->laravel['config']['database.default'])
        ];

        if (empty($connection)) {
            throw new UnexpectedValueException("Invalid database connection [{$db}].");
        }

        if (! empty($connection['url'])) {
            $connection = (new ConfigurationUrlParser)->parseConfiguration($connection);
        }

        if ($this->option('read')) {
            $connection = $this->mergeConnectionConfiguration($connection, 'read');
        } elseif ($this->option('write')) {
            $connection = $this->mergeConnectionConfiguration($connection, 'write');
        } elseif (! $this->option('pooled') && ($connection['driver'] ?? null) === 'pgsql' && ($connection['pooled'] ?? false) === true && ! empty($connection['direct'])) {
            $connection = $this->mergeConnectionConfiguration($connection, 'direct');
        }

        return $connection;
    }

    /**
     * Merge a nested connection configuration onto the base connection.
     *
     * @param  string  $type
     * @return array
     */
    protected function mergeConnectionConfiguration(array $connection, $type)
    {
        if (empty($connection[$type])) {
            return $connection;
        }

        $merge = $connection[$type];

        if (isset($merge[0]) && is_array($merge[0])) {
            $merge = $merge[0];
        }

        if (is_array($merge['host'] ?? null)) {
            $merge['host'] = $merge['host'][0];
        }

        $connection = array_merge($connection, $merge);

        if (is_array($connection['host'] ?? null)) {
            $connection['host'] = $connection['host'][0];
        }

        return Arr::except($connection, ['read', 'write', 'direct', 'pooled']);
    }
}
