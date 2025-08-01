<?php

namespace Glowie\Core\Database\Drivers;

/**
 * Base for Glowie database drivers.
 * @category Database
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 * @see https://glowie.gabrielsilva.dev.br/docs/latest/forms-and-data/databases
 */
interface DbDriver
{

    /**
     * Creates a PDO connection.
     * @param array $database Associative array with the database settings.
     * @return PDO Returns the connection.
     */
    public static function connect(array $database);

    /**
     * Gets the opening escape char for the driver identifiers.
     * @return string Returns the opening escape char.
     */
    public static function getOpeningEscapeChar();

    /**
     * Gets the closing escape char for the driver identifiers.
     * @return string Returns the closing escape char.
     */
    public static function getClosingEscapeChar();
}
