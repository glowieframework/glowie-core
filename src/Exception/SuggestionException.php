<?php

namespace Glowie\Core\Exception;

use Exception;
use Throwable;

/**
 * Exception handler for Glowie application.
 * @category Exception
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 */
class SuggestionException extends Exception
{

    /**
     * The exception suggestion message.
     * @var string|null
     */
    protected $suggestion = null;

    /**
     * Creates a new instance of SuggestionException.
     * @param string $message (Optional) The exception message.
     * @param int $code (Optional) The exception code.
     * @param Throwable|null $previous (Optional) Previous throwable used for exception chaining.
     */
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Gets the suggestion message.
     * @return string|null The suggestion message, if any.
     */
    public function getSuggestion()
    {
        return $this->suggestion;
    }

    /**
     * Sets the suggestion message.
     * @param string|null $suggestion The suggestion message.
     */
    public function setSuggestion(?string $suggestion)
    {
        $this->suggestion = $suggestion;
    }
}
