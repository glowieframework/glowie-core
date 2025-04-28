<?php

namespace Glowie\Core\Exception;

use Exception;
use Throwable;

/**
 * Request exception handler for Glowie application.
 * @category Exception
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 */
class RequestException extends Exception
{

    /**
     * Requested URL that thrown the exception.
     * @var string
     */
    private $url;

    /**
     * The response object, or false if failed.
     * @var Element|bool
     */
    private $response;

    /**
     * Creates a new instance of RequestException.
     * @param string $message (Optional) The exception message.
     * @param int $code (Optional) The exception code.
     * @param Element|bool $response (Optional) The response object, or false if failed.
     * @param Throwable|null $previous (Optional) Previous throwable used for exception chaining.
     */
    public function __construct(string $url, string $message = "", int $code = 0, $response, ?Throwable $previous = null)
    {
        parent::__construct('HttpRequest: ' . $message, $code, $previous);
        $this->url = $url;
        $this->response = $response;
    }

    /**
     * Gets the requested URL that thrown the exception.
     * @return string Exception URL.
     */
    public function getURL()
    {
        return $this->url;
    }

    /**
     * Gets the response object.
     * @return Element|bool Returns false if failed.
     */
    public function getResponse()
    {
        return $this->response;
    }
}
