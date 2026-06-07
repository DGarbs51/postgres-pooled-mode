<?php

namespace DGarbs51\PostgresPooledMode\Schema;

use Illuminate\Database\Schema\PostgresSchemaState as BasePostgresSchemaState;

class PostgresSchemaState extends BasePostgresSchemaState
{
    /**
     * Get the base variables for a dump / load command.
     *
     * @return array
     */
    protected function baseVariables(array $config)
    {
        if (method_exists($this->connection, 'usesDirectConnection') &&
            $this->connection->usesDirectConnection()) {
            $config = $this->connection->getDirectConfig();
        }

        $config['host'] ??= '';

        $variables = [
            'LARAVEL_LOAD_HOST' => is_array($config['host']) ? $config['host'][0] : $config['host'],
            'LARAVEL_LOAD_PORT' => $config['port'] ?? '',
            'LARAVEL_LOAD_USER' => $config['username'],
            'PGPASSWORD' => $config['password'],
        ];

        if (! empty($config['sslmode'])) {
            $variables['PGSSLMODE'] = $config['sslmode'];
        }

        $variables['LARAVEL_LOAD_DATABASE'] = $config['database'];

        return $variables;
    }
}
