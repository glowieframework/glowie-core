<?php

namespace Glowie\Core\Database\Drivers;

use Exception;
use PDO;

/**
 * Glowie database driver for PostgreSQL.
 * @category Database
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 * @see https://glowie.gabrielsilva.dev.br/docs/latest/forms-and-data/databases
 */
class Pgsql implements DbDriver
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
    private const DEFAULT_PORT = 5432;

    /**
     * Creates a PostgreSQL PDO connection.
     * @param array $database Associative array with the database settings.
     * @return PDO Returns the connection.
     */
    public static function connect(array $database)
    {
        // Validate PostgreSQL settings
        if (!extension_loaded('pdo_pgsql')) throw new Exception('Missing "pdo_pgsql" extension in your PHP installation');
        if (empty($database['port'])) $database['port'] = self::DEFAULT_PORT;

        // Creates the connection URL
        $dsn = sprintf("pgsql:host=%s;port=%d;dbname=%s;options='--client_encoding=%s'", $database['host'], $database['port'], $database['db'], $database['charset']);

        // Passes the DB options
        $options = array_replace(self::DEFAULT_OPTIONS, $database['options'] ?? []);

        // Returns the connection
        return new PDO($dsn, $database['username'], $database['password'], $options);
    }

    /**
     * Gets the opening escape char for the driver identifiers.
     * @return string Returns the opening escape char.
     */
    public static function getOpeningEscapeChar()
    {
        return '"';
    }

    /**
     * Gets the closing escape char for the driver identifiers.
     * @return string Returns the closing escape char.
     */
    public static function getClosingEscapeChar()
    {
        return '"';
    }
}
