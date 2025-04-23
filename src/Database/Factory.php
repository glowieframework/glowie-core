<?php

namespace Glowie\Core\Database;

use Exception;
use PDO;
use Throwable;
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
        // Validate common settings
        if (empty($database['host'])) throw new DatabaseException($database, 'Database connection "' . $connection . '" host not defined');
        if (empty($database['username'])) throw new DatabaseException($database, 'Database connection "' . $connection . '" username not defined');
        if (empty($database['db'])) throw new DatabaseException($database, 'Database connection "' . $connection . '" db name not defined');
        if (empty($database['driver'])) $database['driver'] = 'mysql';
        if (empty($database['charset'])) $database['charset'] = 'utf8';

        // Gets the connection driver
        try {
            switch ($database['driver']) {
                case 'mysql':
                    $pdo = self::createMySqlConnection($database);
                    break;

                case 'pgsql':
                    $pdo = self::createPgSqlConnection($database);
                    break;

                default:
                    throw new Exception('Database driver "' . $database['driver'] . '" does not exist');
                    break;
            }

            // Sets the connection handler
            self::$handlers[$connection] = $pdo;
        } catch (Throwable $e) {
            throw new DatabaseException($database, $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Creates a MySql PDO connection.
     * @param array $database Associative array with the database settings.
     * @return PDO Returns the connection.
     */
    private static function createMySqlConnection(array $database)
    {
        // Validate MySQL settings
        if (!extension_loaded('pdo_mysql')) throw new Exception('Missing "pdo_mysql" extension in your PHP installation');
        if (empty($database['port'])) $database['port'] = 3306;

        // Creates the connection URL
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $database['host'], $database['port'], $database['db'], $database['charset']);

        // Passes the DB options
        $options = array_replace([
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ], $database['options'] ?? []);

        // Sets the charset
        $initQuery = ['SET NAMES "' . $database['charset'] . '"'];

        // Sets the strict mode
        if (!empty($database['strict'])) {
            $initQuery[] = 'SET SESSION sql_mode="ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION"';
        } else {
            $initQuery[] = 'SET SESSION sql_mode="ALLOW_INVALID_DATES,NO_ENGINE_SUBSTITUTION"';
        }

        // Runs the init query and returns the PDO
        $pdo = new PDO($dsn, $database['username'], $database['password'], $options);
        @$pdo->exec(implode('; ', $initQuery));
        return $pdo;
    }

    /**
     * Creates a PgSql PDO connection.
     * @param array $database Associative array with the database settings.
     * @return PDO Returns the connection.
     */
    private static function createPgSqlConnection(array $database)
    {
        // Validate PgSQL settings
        if (!extension_loaded('pdo_pgsql')) throw new Exception('Missing "pdo_pgsql" extension in your PHP installation');
        if (empty($database['port'])) $database['port'] = 5432;

        // Creates the connection URL
        $dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s', $database['host'], $database['port'], $database['db']);

        // Passes the DB options
        $options = array_replace([
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ], $database['options'] ?? []);

        // Sets the charset
        $initQuery = ['SET client_encoding TO "' . $database['charset'] . '"'];

        // Runs the init query and returns the PDO
        $pdo = new PDO($dsn, $database['username'], $database['password'], $options);
        @$pdo->exec(implode('; ', $initQuery));
        return $pdo;
    }
}
