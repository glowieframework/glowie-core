<?php

namespace Glowie\Core\Http;

use Glowie\Core\Collection;
use Config;
use Glowie\Core\Tools\Validator;
use Util;
use JsonSerializable;

/**
 * Cookie manager for Glowie application.
 * @category Cookie manager
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 * @see https://glowie.gabrielsilva.dev.br/docs/latest/forms-and-data/cookies
 */
class Cookies implements JsonSerializable
{

    /**
     * Expiration time for **1 minute**.
     * @var int
     */
    public const EXPIRES_MINUTE = 60;

    /**
     * Expiration time for **1 hour**.
     * @var int
     */
    public const EXPIRES_HOUR = 3600;

    /**
     * Expiration time for **1 day**.
     * @var int
     */
    public const EXPIRES_DAY = 86400;

    /**
     * Expiration time of never.
     * @var null
     */
    public const EXPIRES_NEVER = null;

    /**
     * Creates a new instance of the cookie manager.
     * @param array $data (Optional) An associative array with the initial data to store in the cookies.
     */
    public function __construct(array $data = [])
    {
        if (!empty($data)) $this->set($data);
    }

    /**
     * Creates a new cookies instance in a static-binding.
     * @param array $data (Optional) An associative array with the initial data to store in the cookies.
     * @return Cookies New Cookies instance.
     */
    public static function make(array $data = [])
    {
        return new static($data);
    }

    /**
     * Gets the value associated to a key in the cookies data.
     * @param string $key Key to get value.
     * @return mixed Returns the value if exists or null if there is none.
     */
    public function __get(string $key)
    {
        return $this->get($key);
    }

    /**
     * Gets the value associated to a key in the cookies data.
     * @param string $key Key to get value.
     * @param mixed $default (Optional) Default value to return if the key does not exist.
     * @return mixed Returns the value if exists or the default if not.
     */
    public function get(string $key, $default = null)
    {
        return $_COOKIE[$key] ?? $default;
    }

    /**
     * Sets the value for a key in the cookies data.
     * @param string $key Key to set value.
     * @param mixed $value Value to set.
     */
    public function __set(string $key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Sets the value for a key in the cookies.
     * @param string|array $key Key to set value. You can also pass an associative array\
     * of values to set at once and they will be merged into the cookies.
     * @param mixed $value (Optional) Value to set.
     * @param int $expires (Optional) Cookie expiration time in seconds.
     * @return Cookies Current Cookies instance for nested calls.
     */
    public function set($key, $value = null, int $expires = self::EXPIRES_DAY)
    {
        if (is_array($key)) {
            foreach ($key as $field => $value) $this->set($field, $value, $expires);
        } else {
            $_COOKIE[$key] = $value;
            setcookie($key, $value, time() + $expires, '/', '', Config::get('cookies.secure', false), Config::get('cookies.restrict', false));
        }
        return $this;
    }

    /**
     * Sets an encrypted string value in the cookies data.
     * @param string $key Key to set value.
     * @param string $value Value to be store encrypted.
     * @param int $expires (Optional) Cookie expiration time in seconds.
     * @return Cookies Current Cookies instance for nested calls.
     */
    public function setEncrypted(string $key, string $value, int $expires = self::EXPIRES_DAY)
    {
        return $this->set($key, Util::encryptString($value), $expires);
    }

    /**
     * Gets an encrypted string value from the cookies data.
     * @param string $key Key to get value.
     * @param mixed $default (Optional) Default value to return if the key does not exist.
     * @return mixed Returns the decrypted value if exists or the default if not.
     */
    public function getEncrypted(string $key, $default = null)
    {
        $value = $this->get($key);
        if (!$value) return $default;
        return Util::decryptString($value);
    }

    /**
     * Removes the associated key value from the cookies data.
     * @param string $key Key to delete value.
     */
    public function __unset(string $key)
    {
        $this->remove($key);
    }

    /**
     * Removes the associated key value from the cookies data.
     * @param string|array $key Key to delete value. You can also use an array of keys to remove.
     * @return Cookies Current Cookies instance for nested calls.
     */
    public function remove($key)
    {
        foreach ((array)$key as $item) {
            if (array_key_exists($item, $_COOKIE)) {
                $this->set($item, null, -self::EXPIRES_HOUR);
                unset($_COOKIE[$item]);
            }
        }
        return $this;
    }

    /**
     * Removes all cookies data, except the one that matches the specified key.
     * @param string|array $key Key to keep. You can also use an array of keys to keep.
     * @return Cookies Current Cookies instance for nested calls.
     */
    public function only($key)
    {
        $_COOKIE = array_intersect_key($_COOKIE, array_flip((array)$key));
        return $this;
    }

    /**
     * Checks if any value has been associated to a key in the cookies data.
     * @param string $key Key to check.
     * @return bool Returns true or false.
     */
    public function __isset(string $key)
    {
        return array_key_exists($key, $_COOKIE);
    }

    /**
     * Checks if any value has been associated to a key in the cookies data.
     * @param string|array $key Key to check. You can also use an array of keys.
     * @return bool Returns true or false.
     */
    public function has($key)
    {
        $result = false;
        foreach ((array)$key as $item) {
            if ($result) break;
            $result = $this->__isset($item);
        }
        return $result;
    }

    /**
     * Deletes all data from the cookies.
     * @return Cookies Current Cookies instance for nested calls.
     */
    public function flush()
    {
        return $this->remove(array_keys($_COOKIE));
    }

    /**
     * Gets the cookies data as an associative array.
     * @return array The resulting array.
     */
    public function toArray()
    {
        return $_COOKIE;
    }

    /**
     * Gets the cookies data as a Collection.
     * @return Collection The cookies data as a Collection.
     */
    public function toCollection()
    {
        return new Collection($_COOKIE);
    }

    /**
     * Returns the serializable JSON data for the cookies.
     * @return array Cookies data as an associative array.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Gets the cookies data as JSON.
     * @param int $flags (Optional) JSON encoding flags (same as in `json_encode()` function).
     * @param int $depth (Optional) JSON encoding maximum depth (same as in `json_encode()` function).
     * @return string The resulting JSON string.
     */
    public function toJson(int $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES, int $depth = 512)
    {
        return empty($_COOKIE) ? '{}' : json_encode($_COOKIE, $flags, $depth);
    }

    /**
     * Dumps the cookies data.
     * @param bool $plain (Optional) Dump data as plain text instead of HTML.
     */
    public function dump(bool $plain = false)
    {
        Util::dump($this, $plain);
    }

    /**
     * Validates the cookies data using unique validation rules for each one of the fields.
     * @param array $rules Associative array with validation rules for each field.
     * @param bool $bail (Optional) Stop validation of each field after first failure found.
     * @param bool $bailAll (Optional) Stop validation of all fields after first failure found.
     * @return bool Returns true if all rules passed for all fields, false otherwise.
     */
    public function validate(array $rules, bool $bail = false, bool $bailAll = false)
    {
        return (new Validator())->validateFields($this->toArray(), $rules, $bail, $bailAll);
    }

    /**
     * Validates the cookies data using the same rules for all values.
     * @param string|array $rules Validation rules for the data. Can be a single rule or an array of rules.
     * @param bool $bail (Optional) Stop validation of each value after first failure found.
     * @param bool $bailAll (Optional) Stop validation of all values after first failure found.
     * @return bool Returns true if all rules passed for all values, false otherwise.
     */
    public function validateAll($rules, bool $bail = false, bool $bailAll = false)
    {
        return (new Validator())->validateMultiple($this->toArray(), $rules, $bail, $bailAll);
    }

    /**
     * Gets the cookies data as a string (data will be serialized as JSON).
     * @return string The resulting JSON string.
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * Cookies debugging information.
     */
    public function __debugInfo()
    {
        return $_COOKIE;
    }
}
