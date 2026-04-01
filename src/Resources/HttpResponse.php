<?php

namespace Glowie\Core\Resources;

use Glowie\Core\Element;
use Util;

/**
 * Resource to abstract an HTTP response.
 * @category Resource
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 */
class HttpResponse extends Element
{

    /**
     * Checks if the HTTP response return a status code between 200 and 300
     * @return bool Returns true if the response was successful.
     */
    public function isSuccessful()
    {
        return $this->get('success', false);
    }

    /**
     * Gets the HTTP response body.
     * @return string Raw body content.
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Gets the HTTP response status code.
     * @return int Status code.
     */
    public function getStatusCode()
    {
        return $this->status;
    }

    /**
     * Checks if a header is present in the HTTP response.
     * @param string $name Header name to check.
     * @return bool Returns true if the header is present, false otherwise.
     */
    public function hasHeader(string $name)
    {
        return $this->headers->has($name);
    }

    /**
     * Returns a list of the HTTP response headers.
     * @return Element Returns an Element with the headers.
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Gets the value of a header from the HTTP response.
     * @param string $name Header name to get.
     * @param mixed $default (Optional) Default value to return if the header does not exist.
     * @return string|null Returns the value if exists or the default if not.
     */
    public function getHeader(string $name, $default = null)
    {
        return $this->headers->get($name, $default);
    }

    /**
     * Returns a JSON key from the HTTP response.
     * @param string|null $key (Optional) Key to get value (accepts dot notation keys). Leave empty to get the whole JSON Element.
     * @param mixed $default (Optional) Default value to return if the key does not exist.
     * @return mixed Returns the value if the key exists (or the default if not) or the JSON Element if a key is not provided.
     */
    public function getJson(?string $key = null, $default = null)
    {
        if (Util::isEmpty($key)) return $this->json;
        return $this->json->get($key, $default);
    }

    /**
     * Gets the body data used to perform the request.
     * @return mixed Returns the request body.
     */
    public function getRequestBody()
    {
        return $this->request_body;
    }

    /**
     * Gets the request URL.
     * @return string Returns the request URL.
     */
    public function getRequestUrl()
    {
        return $this->request_url;
    }

    /**
     * Gets a resource parameter using a magic method.
     * @param string $method Parameter name to get.
     * @param array $args Unused.
     * @return mixed Returns the parameter if exists, null otherwise.
     */
    public function __call(string $method, array $args)
    {
        return $this->get($method);
    }
}
