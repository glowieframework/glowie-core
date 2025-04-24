<?php

namespace Glowie\Core\Database\Drivers;

use Exception;
use PDO;

/**
 * Glowie database driver for SQL Server.
 * @category Database
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 * @see https://glowie.gabrielsilva.dev.br/docs/latest/forms-and-data/databases
 */
class Sqlsrv
{
    /**
     * Default PDO options.
     * @var array
     */
    private const DEFAULT_OPTIONS = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ];

    /**
     * Default port.
     * @var int
     */
    public const DEFAULT_PORT = 1433;

    /**
     * Identifier escaping characters.
     * @var array
     */
    public const ESCAPING_CHARS = ['[', ']'];

    /**
     * Creates a SQL Server PDO connection.
     * @param array $database Associative array with the database settings.
     * @return PDO Returns the connection.
     */
    public static function connect(array $database)
    {
        // Validate SQL Server settings
        if (!extension_loaded('pdo_sqlsrv') && !extension_loaded('pdo_dblib')) throw new Exception('Missing "pdo_sqlsrv" or "pdo_dblib" extensions in your PHP installation');
        if (empty($database['port'])) $database['port'] = self::DEFAULT_PORT;

        // Creates the connection URL
        if (extension_loaded('pdo_sqlsrv')) {
            $dsn = sprintf('sqlsrv:Server=%s,%d;Database=%s', $database['host'], $database['port'], $database['db']);
        } else {
            $dsn = sprintf('dblib:host=%s:%d;dbname=%s;charset=%s', $database['host'], $database['port'], $database['db'], $database['charset']);
        }

        // Passes the DB options
        $options = array_replace(self::DEFAULT_OPTIONS, $database['options'] ?? []);

        // Returns the connection
        return new PDO($dsn, $database['username'], $database['password'], $options);
    }
}
