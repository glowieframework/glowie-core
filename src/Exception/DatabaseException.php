<?php

namespace Glowie\Core\Exception;

use Exception;
use Throwable;

/**
 * Database exception handler for Glowie application.
 * @category Exception
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 */
class DatabaseException extends Exception
{

    /**
     * Database connection settings.
     * @var array
     */
    private $database;

    /**
     * Creates a new instance of DatabaseException.
     * @param array $database Database connection settings.
     * @param string $message (Optional) The exception message.
     * @param int|string $code (Optional) The exception code.
     * @param Throwable|null $previous (Optional) Previous throwable used for exception chaining.
     */
    public function __construct(array $database, string $message = "", $code = 0, ?Throwable $previous = null)
    {
        parent::__construct('Database: ' . $message, $code, $previous);
        $this->database = $database;
    }

    /**
     * Gets the database connection settings.
     * @return array Database connection settings.
     */
    public function getDatabase()
    {
        return $this->database;
    }
}
