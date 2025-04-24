<?php

namespace Glowie\Core\Database\Drivers;

use Exception;
use PDO;

/**
 * Glowie database driver for SQLite.
 * @category Database
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 * @see https://glowie.gabrielsilva.dev.br/docs/latest/forms-and-data/databases
 */
class Sqlite
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
     * Identifier escaping characters.
     * @var array
     */
    public const ESCAPING_CHARS = ['`', '`'];

    /**
     * Creates a Sqlite PDO connection.
     * @param array $database Associative array with the database settings.
     * @return PDO Returns the connection.
     */
    public static function connect(array $database)
    {
        // Validate sqlite settings
        if (!extension_loaded('pdo_sqlite')) throw new Exception('Missing "pdo_sqlite" extension in your PHP installation');

        // Creates the connection URL
        $dsn = 'sqlite:' . $database['path'];

        // Passes the DB options
        $options = array_replace(self::DEFAULT_OPTIONS, $database['options'] ?? []);

        // Returns the connection
        return new PDO($dsn, null, null, $options);
    }
}
