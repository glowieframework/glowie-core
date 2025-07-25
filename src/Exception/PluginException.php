<?php

namespace Glowie\Core\Exception;

use Throwable;

/**
 * File exception handler for Glowie application.
 * @category Exception
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 */
class PluginException extends SuggestionException
{

    /**
     * Creates a new instance of PluginException.
     * @param string $message (Optional) The exception message.
     * @param int $code (Optional) The exception code.
     * @param Throwable|null $previous (Optional) Previous throwable used for exception chaining.
     */
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct('Plugin: ' . $message, $code, $previous);
    }
}
