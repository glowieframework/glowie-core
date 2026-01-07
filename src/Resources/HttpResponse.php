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
     * Checks if a header is present in the HTTP response.
     * @param string $name Header name to check.
     * @return bool Returns true if the header is present, false otherwise.
     */
    public function hasHeader(string $name)
    {
        return $this->headers->has($name);
    }

    /**
     * Gets the value of a header from the HTTP response.
     * @param string $name Header name to get.
     * @param mixed $default (Optional) Default value to return if the header does not exist.
     * @return mixed Returns the value if exists or the default if not.
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
