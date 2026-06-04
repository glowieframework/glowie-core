<?php

namespace Glowie\Core\Exception;

use Throwable;

/**
 * Http exception handler for Glowie application.
 * @category Exception
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 */
class HttpException extends SuggestionException
{

    /**
     * Creates a new instance of HttpException.
     * @param int $code (Optional) The exception code.
     * @param string $message (Optional) The exception message.
     * @param Throwable|null $previous (Optional) Previous throwable used for exception chaining.
     */
    public function __construct(int $code = 0, string $message = "", ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
