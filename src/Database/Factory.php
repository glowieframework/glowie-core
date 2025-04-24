<?php

namespace Glowie\Core\Database;

use Exception;
use PDO;
use Throwable;
use Util;
use Glowie\Core\Exception\DatabaseException;

/**
 * Database factory for Glowie application.
 * @category Database
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 * @see https://glowie.gabrielsilva.dev.br/docs/latest/forms-and-data/databases
 */
class Factory
{

    /**
     * Database connection handlers.
     * @var array
     */
    private static $handlers = [];

    /**
     * Query listeners.
     * @var array
     */
    private static $listeners = [];

    /**
     * Gets a connection handler.
     * @param string $name Connection name.
     * @return PDO|null The connection instance if exists or null if not.
     */
    public static function getHandler(string $name)
    {
        return self::$handlers[$name] ?? null;
    }

    /**
     * Setup a query listener.
     * @param callable $callback Listener callback function. It receives the query, bindings, duration (ms) and status as parameters.
     */
    public static function listen(callable $callback)
    {
        self::$listeners[] = $callback;
    }

    /**
     * Notify query listeners that a query ran.
     * @param string $query SQL query.
     * @param array $bindings Prepared bindings.
     * @param float $time Query duration in microsseconds.
     * @param bool $status Query status, true for success, false for fail.
     */
    public static function notifyListeners(string $query, array $bindings, float $time, bool $status)
    {
        if (empty(self::$listeners)) return;

        foreach (self::$listeners as $item) {
            call_user_func_array($item, [$query, $bindings, round($time * 1000, 2), $status]);
        }
    }

    /**
     * Creates a database connection.
     * @param string $connection Connection name.
     * @param array $database Associative array with the database settings.
     * @throws DatabaseException Throws an exception if the connection fails.
     */
    public static function createConnection(string $connection, array $database)
    {
        // Set default settings
        if (empty($database['driver'])) $database['driver'] = 'mysql';
        if (empty($database['charset'])) $database['charset'] = 'utf8';

        // Validate common settings
        if ($database['driver'] === 'sqlite') {
            if (empty($database['path'])) throw new DatabaseException($database, 'Database connection "' . $connection . '" path not defined');
        } else {
            if (empty($database['host'])) throw new DatabaseException($database, 'Database connection "' . $connection . '" host not defined');
            if (empty($database['username'])) throw new DatabaseException($database, 'Database connection "' . $connection . '" username not defined');
            if (empty($database['db'])) throw new DatabaseException($database, 'Database connection "' . $connection . '" db not defined');
        }

        try {
            // Gets the connection driver
            $driverClass = 'Glowie\Core\Database\Drivers\\' . Util::pascalCase($database['driver']);
            if (!class_exists($driverClass)) throw new Exception('Database driver "' . $database['driver'] . '" is not available');

            // Sets the connection handler
            self::$handlers[$connection] = $driverClass::connect($database);
        } catch (Throwable $e) {
            throw new DatabaseException($database, $e->getMessage(), $e->getCode(), $e);
        }
    }
}
