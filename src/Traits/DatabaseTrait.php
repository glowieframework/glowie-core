<?php

namespace Glowie\Core\Traits;

use Exception;
use Throwable;
use stdClass;
use Closure;
use Config;
use PDO;
use PDOException;
use Glowie\Core\Exception\QueryException;
use Glowie\Core\Element;
use Glowie\Core\Database\Factory;
use Glowie\Core\Exception\DatabaseException;
use Util;

/**
 * Database handler trait for Glowie application.
 * @category Database
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 * @see https://glowie.gabrielsilva.dev.br/docs/latest/forms-and-data/databases
 */
trait DatabaseTrait
{

    /**
     * Query instruction.
     * @var string
     */
    private $_instruction;

    /**
     * Stores if there is a transaction running.
     * @var bool
     */
    private $_transaction;

    /**
     * Current working table.
     * @var string
     */
    private $_table;

    /**
     * Current connection name.
     * @var string
     */
    private $_connection;

    /**
     * Raw query.
     * @var string
     */
    private $_raw;

    /**
     * Prepared statement.
     * @var array
     */
    private $_prepared;

    /**
     * Return results as associative arrays.
     * @var bool
     */
    private $_returnAssoc = false;

    /**
     * Last insert ID.
     * @var int|null
     */
    private $_lastInsertId = null;

    /**
     * Affected rows count.
     * @var int
     */
    private $_affectedRows = 0;

    /**
     * Returns next results as associative arrays.
     * @return $this Current instance for nested calls.
     */
    public function asArray()
    {
        $this->_returnAssoc = true;
        return $this;
    }

    /**
     * Returns next results as Element objects.
     * @return $this Current instance for nested calls.
     */
    public function asElement()
    {
        $this->_returnAssoc = false;
        return $this;
    }

    /**
     * Sets the working table.
     * @param string $table Table name to set as the working table.
     * @return $this Current instance for nested calls.
     */
    public function table(string $table)
    {
        $this->_table = $this->escapeIdentifier($table);
        return $this;
    }

    /**
     * Sets the database connection.
     * @param string $database Database connection name (from your app configuration).
     * @return $this Current instance for nested calls.
     * @throws DatabaseException Throws an exception if the connection fails.
     */
    public function database(string $database)
    {
        $this->_connection = $database;
        if (!$this->getConnection()) $this->reconnect();
        return $this;
    }

    /**
     * Returns the current database connection handler.
     * @return PDO|null The connection instance or null on errors.
     */
    public function getConnection()
    {
        return Factory::getHandler($this->_connection);
    }

    /**
     * Refreshes the database connection.
     */
    public function reconnect()
    {
        // Gets connection configuration
        $database = Config::get("database.{$this->_connection}");
        if (empty($database)) throw new DatabaseException([], 'Database connection setting "' . $this->_connection . '" not found in your app configuration');

        // Creates the database connection
        Factory::createConnection($this->_connection, $database);
    }

    /**
     * Gets the current database driver name.
     * @return string Returns the driver name.
     */
    public function getDriver()
    {
        return $this->getConnection()->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    /**
     * Escapes special characters in a string, preventing SQL injections.
     * @param mixed $string String to escape.
     * @return string Escaped string.
     */
    public function escape($string)
    {
        if ($string instanceof stdClass) return $string->value;
        return $this->getConnection()->quote((string)$string);
    }

    /**
     * Escapes a table/column identifier.
     * @param mixed $name Identifier to be escaped.
     * @return string Returns the escaped identifier.
     */
    public function escapeIdentifier($name)
    {
        // Checks for raw identifier
        if ($name instanceof stdClass) return $name->value;
        $name = trim($name);

        // Gets the characters
        $driver = $this->getDriver();
        $driverClass = 'Glowie\Core\Database\Drivers\\' . Util::pascalCase($driver);
        $start = $driverClass::getOpeningEscapeChar();
        $end = $driverClass::getClosingEscapeChar();

        // Checks for SQL functions
        if (preg_match('/^[a-z_][a-z0-9_]*\s*\([^()]*\)$/i', $name)) {
            return $name;
        }

        // Checks for AS alias
        if (preg_match('/ +AS +/i', $name)) {
            list($ident, $alias) = preg_split('/ +AS +/i', $name, 2);
            return $this->escapeIdentifier(trim($ident)) . ' AS ' . $this->escapeIdentifier(trim($alias));
        }

        // Checks for implicit alias
        if (preg_match('/(.+?) +([^\s]+)/', $name, $m)) {
            return $this->escapeIdentifier(trim($m[1])) . ' ' . $this->escapeIdentifier(trim($m[2]));
        }

        // Split schema parts and escapes each one of them
        $parts = explode('.', $name);
        $escaped = array_map(function ($part) use ($start, $end) {
            $part = trim($part);
            if ($part === '*') return '*';
            if (Util::startsWith($part, $start) && Util::endsWith($part, $end)) return $part;
            return $start . $part . $end;
        }, $parts);

        // Returns the result
        return implode('.', $escaped);
    }

    /**
     * Returns a value that will not be escaped or quoted into the query.
     * @param mixed $value Value to be returned.
     * @return stdClass Value representation as a generic object.
     */
    public static function raw($value)
    {
        $obj = new stdClass();
        $obj->value = $value;
        return $obj;
    }

    /**
     * Runs a raw SQL query.
     * @param string $query Full raw query to run.
     * @param bool $return (Optional) Set to **true** if the query should return any results.
     * @return array|bool If the query is successful and should return results, will return an array with\
     * the results. Otherwise returns true on success or false on failure.
     * @throws QueryException Throws an exception if the query fails.
     */
    public function query(string $query, bool $return = true)
    {
        $this->_raw = $query;
        return $this->execute($return);
    }

    /**
     * Runs a raw prepared SQL query.
     * @param string $query Full raw query to run with question marks in prepared parameters.
     * @param array $params (Optional) Array of parameters to bind in order of each question mark in the query.
     * @param bool $return (Optional) Set to **true** if the query should return any results.
     * @return array|bool If the query is successful and should return results, will return an array with\
     * the results. Otherwise returns true on success or false on failure.
     * @throws QueryException Throws an exception if the query fails.
     */
    public function prepared(string $query, array $params = [], bool $return = true)
    {
        $this->_prepared = [$query, $params];
        return $this->execute($return);
    }

    /**
     * Begins an SQL transaction for the next queries.
     * @return bool Returns true on success or false on failure.
     * @throws Exception Throws an exception if a pending transaction is already running.
     */
    public function beginTransaction()
    {
        if ($this->_transaction) throw new Exception('DB: There is a pending transaction already running');
        $this->_transaction = true;
        return $this->getConnection()->beginTransaction();
    }

    /**
     * Commits the current SQL transaction.
     * @return bool Returns true on success or false on failure.
     * @throws Exception Throws an exception if there is not a running transaction.
     */
    public function commit()
    {
        if (!$this->_transaction) throw new Exception('DB: There is not a running transaction');
        $this->_transaction = false;
        return $this->getConnection()->commit();
    }

    /**
     * Rolls back the current SQL transaction.
     * @return bool Returns true on success or false on failure.
     * @throws Exception Throws an exception if there is not a running transaction.
     */
    public function rollback()
    {
        if (!$this->_transaction) throw new Exception('DB: There is not a running transaction');
        $this->_transaction = false;
        return $this->getConnection()->rollback();
    }

    /**
     * Enclosures a set of operations in a transaction.
     * @param Closure $operations Set of operations to run inside the transaction.
     * @return bool Returns true on success or false on failure.
     * @throws QueryException Throws an exception if the query fails.
     */
    public function transaction(Closure $operations)
    {
        // Begins the transaction
        $this->beginTransaction();

        try {
            // Run operations
            call_user_func_array($operations, [$this]);
        } catch (Throwable $e) {
            // If something fails, rolls back the transaction
            return $this->rollback();
        }

        // Commits the transaction if nothing failed
        return $this->commit();
    }

    /**
     * Disables FOREIGN KEY checks for the current session.
     * @return bool Returns true on success or false on failure.
     * @throws QueryException Throws an exception if the query fails.
     */
    public function disableFkChecks()
    {
        if ($this->getDriver() === 'sqlite') return $this->query('PRAGMA foreign_keys = OFF', false);
        return $this->query('SET FOREIGN_KEY_CHECKS = 0', false);
    }

    /**
     * Enables FOREIGN KEY checks for the current session.
     * @return bool Returns true on success or false on failure.
     * @throws QueryException Throws an exception if the query fails.
     */
    public function enableFkChecks()
    {
        if ($this->getDriver() === 'sqlite') return $this->query('PRAGMA foreign_keys = ON', false);
        return $this->query('SET FOREIGN_KEY_CHECKS = 1', false);
    }

    /**
     * Runs the current built query.
     * @param bool $returns (Optional) If the query should return a result.
     * @param bool $returnsFirst (Optional) If the query should return a single result.
     * @return mixed If the query is successful and should return any results, will return an Element/associative array with the\
     * first result or an array of results. Otherwise returns true on success or false on failure.
     * @throws QueryException Throws an exception if the query fails.
     */
    private function execute(bool $returns = false, bool $returnsFirst = false)
    {
        try {
            // Store query start time
            $queryStart = microtime(true);

            // Run query or prepared statement
            if (!empty($this->_prepared)) {
                // Prepared query
                [$sql, $params] = $this->_prepared;
                $stmt = $this->getConnection()->prepare($sql);
                $stmt->execute($params);
                Factory::notifyListeners($this->_connection, $sql, $params, microtime(true) - $queryStart, true);
            } else {
                // Raw query
                $sql = $this->getQuery();
                $stmt = $this->getConnection()->query($sql);
                Factory::notifyListeners($this->_connection, $sql, [], microtime(true) - $queryStart, true);
            }

            // Clear query data
            $this->clearQuery();
            $result = true;

            // Checks for return type
            if ($returns) {
                if ($returnsFirst) {
                    // Returns only first row
                    $result = null;
                    $row = $stmt->fetch();
                    if ($row !== false) $result = $this->_returnAssoc ? $row : new Element($row);
                } else {
                    // Returns all rows
                    $rows = $stmt->fetchAll();
                    $result = $this->_returnAssoc ? $rows : array_map(fn($r) => new Element($r), $rows);
                }
            }

            // Stores the last insert ID
            if (Util::startsWith(trim(mb_strtoupper($sql)), 'INSERT')) {
                $this->_lastInsertId = (int)$this->getConnection()->lastInsertId();
            } else {
                $this->_lastInsertId = null;
            }

            // Store affected rows and returns the result
            $this->_affectedRows = $stmt->rowCount();
            return $result;
        } catch (PDOException $e) {
            // Notify listeners of failure
            Factory::notifyListeners($this->_connection, $sql, $params ?? [], microtime(true) - $queryStart, false);

            // Query failed with error
            throw new QueryException($sql, $e->getMessage(), $e->getCode(), $e);
        }
    }
}
