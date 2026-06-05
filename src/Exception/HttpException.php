<?php

namespace Glowie\Core\Exception;

use Glowie\Core\Http\Response;
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
     * Default HTTP error messages.
     * @var array
     */
    private const DEFAULT_MESSAGES = [
        Response::HTTP_BAD_REQUEST => 'Bad Request',
        Response::HTTP_UNAUTHORIZED => 'Unauthorized',
        Response::HTTP_FORBIDDEN => 'Forbidden',
        Response::HTTP_NOT_FOUND => 'Not Found',
        Response::HTTP_METHOD_NOT_ALLOWED => 'Method Not Allowed',
        Response::HTTP_NOT_ACCEPTABLE => 'Not Acceptable',
        Response::HTTP_REQUEST_TIMEOUT => 'Request Timeout',
        Response::HTTP_CONFLICT => 'Conflict',
        Response::HTTP_GONE => 'Gone',
        Response::HTTP_PAYLOAD_TOO_LARGE => 'Payload Too Large',
        Response::HTTP_UNPROCESSABLE_ENTITY => 'Unprocessable Entity',
        Response::HTTP_TOO_MANY_REQUESTS => 'Too Many Requests',
        Response::HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE => 'Request Header Fields Too Large',
        Response::HTTP_UNAVAILABLE_FOR_LEGAL_REASONS => 'Unavailable For Legal Reasons',
        Response::HTTP_INTERNAL_SERVER_ERROR => 'Internal Server Error',
        Response::HTTP_NOT_IMPLEMENTED => 'Not Implemented',
        Response::HTTP_BAD_GATEWAY => 'Bad Gateway',
        Response::HTTP_SERVICE_UNAVAILABLE => 'Service Unavailable',
        Response::HTTP_GATEWAY_TIMEOUT => 'Gateway Timeout',
    ];

    /**
     * Creates a new instance of HttpException.
     * @param int $code (Optional) The exception code.
     * @param string $message (Optional) The exception message.
     * @param Throwable|null $previous (Optional) Previous throwable used for exception chaining.
     */
    public function __construct(int $code = 0, string $message = "", ?Throwable $previous = null)
    {
        if (empty($message) && !empty(self::DEFAULT_MESSAGES[$code])) $message = self::DEFAULT_MESSAGES[$code];
        parent::__construct($message, $code, $previous);
    }
}
