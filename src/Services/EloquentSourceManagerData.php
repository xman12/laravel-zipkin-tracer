<?php

namespace ZipkinTracer\Services;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Events\{TransactionBeginning, TransactionCommitted, TransactionRolledBack, QueryExecuted};
use ZipkinTracer\DTO\DBQueryDTO;

class EloquentSourceManagerData
{
    protected array $queries = [];

    public function __construct(private ConnectionResolverInterface $databaseManager, private EventDispatcher $eventDispatcher)
    {
    }

    /**
     * @param QueryExecuted $event
     * @return void
     */
    public function addQuery(QueryExecuted $event): void
    {
        $trace = StackTrace::getFromDebugBackTrace();
        $executeFile = $trace->resolveExecuteFile();

        $queryDTO = new DBQueryDTO(
            $this->createRawQuery($event->sql, $event->bindings, $event->connectionName),
            $event->time / 1000,
            $event->connectionName,
            microtime(true) - $event->time / 1000,
            $executeFile->getFile(),
            $executeFile->getLine()
        );
        $this->queries[] = $queryDTO;
    }

    /**
     * @param TransactionBeginning|TransactionCommitted|TransactionRolledBack $event
     * @return void
     */
    public function addTransactionQuery(TransactionBeginning|TransactionCommitted|TransactionRolledBack $event): void
    {
        switch (true) {
            case $event instanceof TransactionBeginning:
                $type = 'START TRANSACTION';
                break;
            case $event instanceof TransactionCommitted:
                $type = 'COMMITTED';
                break;
            case $event instanceof TransactionRolledBack:
                $type = 'ROLLBACK';
                break;
        }
        $trace = Trace::getFromDebugBackTrace();
        $executeFile = $trace->resolveExecuteFile();

        $queryDTO = new DBQueryDTO(
            $type,
            0,
            $event->connectionName,
            microtime(true),
            $executeFile->getFile(),
            $executeFile->getLine()
        );

        $this->queries[] = $queryDTO;
    }

    private function createRawQuery($query, $bindings, $connection)
    {
        // add bindings to query
        $bindings = $this->databaseManager->connection($connection)->prepareBindings($bindings);

        $index = 0;
        $query = preg_replace_callback('/\?/', function ($matches) use ($bindings, $connection, &$index) {
            $binding = $this->quoteBinding($bindings[$index++], $connection);

            // convert binary bindings to hexadecimal representation
            if (!preg_match('//u', (string)$binding)) $binding = '0x' . bin2hex($binding);

            // escape backslashes in the binding (preg_replace requires to do so)
            return (string)$binding;
        }, $query, count($bindings));

        // highlight keywords
        $keywords = [
            'select', 'insert', 'update', 'delete', 'into', 'values', 'set', 'where', 'from', 'limit', 'is', 'null',
            'having', 'group by', 'order by', 'asc', 'desc'
        ];
        $regexp = '/\b' . implode('\b|\b', $keywords) . '\b/i';

        return preg_replace_callback($regexp, function ($match) {
            return strtoupper($match[0]);
        }, $query);
    }

    private function quoteBinding($binding, $connection)
    {
        $connection = $this->databaseManager->connection($connection);

        if (!method_exists($connection, 'getPdo')) return;

        $pdo = $connection->getPdo();

        if (null === $pdo) return;

        $attrDriverNames = ['odbc', 'crate'];
        if (in_array($pdo->getAttribute(\PDO::ATTR_DRIVER_NAME), $attrDriverNames)) {
            $binding = is_object($binding) ? json_encode($binding) : $binding;
            return "'" . str_replace("'", "''", $binding) . "'";
        }

        return is_string($binding) ? $pdo->quote($binding) : $binding;
    }

    /**
     * @return DBQueryDTO[]
     */
    public function getQueries(): array
    {
        return $this->queries;
    }
}
