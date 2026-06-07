<?php

namespace DGarbs51\PostgresPooledMode\Console;

use DGarbs51\PostgresPooledMode\Console\Concerns\ResolvesDirectConnection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Console\TableCommand as BaseTableCommand;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

use function Laravel\Prompts\search;

class TableCommand extends BaseTableCommand
{
    use ResolvesDirectConnection;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(ConnectionResolverInterface $connections)
    {
        $connection = $this->resolveConnection($connections, $this->input->getOption('database'));
        $tables = (new Collection($connection->getSchemaBuilder()->getTables()))
            ->keyBy('schema_qualified_name')->all();

        $tableNames = (new Collection($tables))->keys();

        $tableName = $this->argument('table') ?: search(
            'Which table would you like to inspect?',
            fn (string $query) => $tableNames
                ->filter(fn ($table) => str_contains(strtolower($table), strtolower($query)))
                ->values()
                ->all()
        );

        $table = $tables[$tableName] ?? (new Collection($tables))->when(
            Arr::wrap($connection->getSchemaBuilder()->getCurrentSchemaListing()
                ?? $connection->getSchemaBuilder()->getCurrentSchemaName()),
            fn (Collection $collection, array $currentSchemas) => $collection->sortBy(
                function (array $table) use ($currentSchemas) {
                    $index = array_search($table['schema'], $currentSchemas);

                    return $index === false ? PHP_INT_MAX : $index;
                }
            )
        )->firstWhere('name', $tableName);

        if (! $table) {
            $this->components->warn("Table [{$tableName}] doesn't exist.");

            return 1;
        }

        [$columns, $indexes, $foreignKeys] = $connection->withoutTablePrefix(function ($connection) use ($table) {
            /** @var Builder $schema */
            $schema = $connection->getSchemaBuilder();
            $tableName = $table['schema_qualified_name'];

            return [
                $this->columns($schema, $tableName),
                $this->indexes($schema, $tableName),
                $this->foreignKeys($schema, $tableName),
            ];
        });

        $data = [
            'table' => [
                'schema' => $table['schema'],
                'name' => $table['name'],
                'schema_qualified_name' => $table['schema_qualified_name'],
                'columns' => count($columns),
                'size' => $table['size'],
                'comment' => $table['comment'],
                'collation' => $table['collation'],
                'engine' => $table['engine'],
            ],
            'columns' => $columns,
            'indexes' => $indexes,
            'foreign_keys' => $foreignKeys,
        ];

        $this->display($data);

        return 0;
    }
}
