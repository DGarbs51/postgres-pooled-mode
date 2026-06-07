<?php

namespace DGarbs51\PostgresPooledMode\Console;

use DGarbs51\PostgresPooledMode\Console\Concerns\ResolvesDirectConnection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Console\ShowCommand as BaseShowCommand;

class ShowCommand extends BaseShowCommand
{
    use ResolvesDirectConnection;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(ConnectionResolverInterface $connections)
    {
        $connection = $this->resolveConnection($connections, $database = $this->input->getOption('database'));

        $schema = $connection->getSchemaBuilder();

        $data = [
            'platform' => [
                'config' => $this->getConfigFromDatabase($database),
                'name' => $connection->getDriverTitle(),
                'connection' => $connection->getName(),
                'version' => $connection->getServerVersion(),
                'open_connections' => $connection->threadCount(),
            ],
            'tables' => $this->tables($connection, $schema),
        ];

        if ($this->option('views')) {
            $data['views'] = $this->views($connection, $schema);
        }

        if ($this->option('types')) {
            $data['types'] = $this->types($connection, $schema);
        }

        $this->display($data);

        return 0;
    }
}
