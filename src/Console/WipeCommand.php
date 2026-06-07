<?php

namespace DGarbs51\PostgresPooledMode\Console;

use DGarbs51\PostgresPooledMode\Console\Concerns\ResolvesDirectConnection;
use Illuminate\Database\Console\WipeCommand as BaseWipeCommand;

class WipeCommand extends BaseWipeCommand
{
    use ResolvesDirectConnection;

    /**
     * Drop all of the database tables.
     *
     * @param  string|null  $database
     * @return void
     */
    protected function dropAllTables($database)
    {
        $this->resolveConnection($this->laravel['db'], $database)
            ->getSchemaBuilder()
            ->dropAllTables();
    }

    /**
     * Drop all of the database views.
     *
     * @param  string|null  $database
     * @return void
     */
    protected function dropAllViews($database)
    {
        $this->resolveConnection($this->laravel['db'], $database)
            ->getSchemaBuilder()
            ->dropAllViews();
    }

    /**
     * Drop all of the database types.
     *
     * @param  string|null  $database
     * @return void
     */
    protected function dropAllTypes($database)
    {
        $this->resolveConnection($this->laravel['db'], $database)
            ->getSchemaBuilder()
            ->dropAllTypes();
    }

    /**
     * Flush the given database connection.
     *
     * @param  string|null  $database
     * @return void
     */
    protected function flushDatabaseConnection($database)
    {
        $this->resolveConnection($this->laravel['db'], $database)->disconnect();
    }
}
