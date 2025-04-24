<?php

namespace Glowie\Core\Database\Drivers;

use Exception;
use PDO;

/**
 * Glowie database driver for MySQL / MariaDB.
 * @category Database
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 * @see https://glowie.gabrielsilva.dev.br/docs/latest/forms-and-data/databases
 */
class Mysql
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
    public const DEFAULT_PORT = 3306;

    /**
     * Identifier escaping characters.
     * @var array
     */
    public const ESCAPING_CHARS = ['`', '`'];

    /**
     * Creates a MySQL PDO connection.
     * @param array $database Associative array with the database settings.
     * @return PDO Returns the connection.
     */
    public static function connect(array $database)
    {
        // Validate MySQL settings
        if (!extension_loaded('pdo_mysql')) throw new Exception('Missing "pdo_mysql" extension in your PHP installation');
        if (empty($database['port'])) $database['port'] = self::DEFAULT_PORT;

        // Creates the connection URL
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $database['host'], $database['port'], $database['db'], $database['charset']);

        // Sets the strict mode
        if (!empty($database['strict'])) {
            $initQuery = 'SET SESSION sql_mode="ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION"';
        } else {
            $initQuery = 'SET SESSION sql_mode="ALLOW_INVALID_DATES,NO_ENGINE_SUBSTITUTION"';
        }

        // Passes the DB options
        $options = array_replace(self::DEFAULT_OPTIONS, [PDO::MYSQL_ATTR_INIT_COMMAND => $initQuery], $database['options'] ?? []);

        // Returns the connection
        return new PDO($dsn, $database['username'], $database['password'], $options);
    }
}
