<?php

namespace Glowie\Core\Traits;

use Glowie\Core\Collection;
use Glowie\Core\Tools\Validator;
use Util;

/**
 * Generic safe object trait for Glowie application.
 * @category Object
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 * @see https://glowie.gabrielsilva.dev.br/docs/latest/forms-and-data/element
 */
trait ElementTrait
{

    /**
     * Element data.
     * @var array
     */
    private $__data = [];

    /**
     * Constructs the Element trait data.
     * @param array $data (Optional) An associative array with the initial data to parse.
     */
    private function __constructTrait(array $data = [])
    {
        if (!empty($data)) $this->__data = $data;
    }

    /**
     * Gets the value associated to a key in the Element data.
     * @param string $key Key to get value.
     * @return mixed Returns the value if exists or null if there is none.
     */
    public function __get(string $key)
    {
        return $this->get($key);
    }

    /**
     * Gets the value associated to a key in the Element data using a magic method.
     * @param string $method Method name / key to get value.
     * @param array $args Method args (unused).
     * @return mixed Returns the value if exists or null if there is none.
     */
    public function __call(string $method, array $args)
    {
        return $this->get($method);
    }

    /**
     * Gets the value associated to a key in the Element data.
     * @param string $key Key to get value (accepts dot notation keys).
     * @param mixed $default (Optional) Default value to return if the key does not exist.
     * @return mixed Returns the value if exists or the default if not.
     */
    public function get(string $key, $default = null)
    {
        return Util::arrayGet($this->__data, $key, $default);
    }

    /**
     * Sets the value for a key in the Element data.
     * @param string $key Key to set value.
     * @param mixed $value Value to set.
     */
    public function __set(string $key, $value)
    {
        $this->set($key, $value, true);
    }

    /**
     * Sets the value for a key in the Element data.
     * @param string|array $key Key to set value (accepts dot notation keys). You can also pass an associative array\
     * of values to set at once and they will be merged into the Element data.
     * @param mixed $value (Optional) Value to set.
     * @param bool $ignoreDot (Optional) Ignore dot notation keys.
     * @return Element Current Element instance for nested calls.
     */
    public function set($key, $value = null, bool $ignoreDot = false)
    {
        if (is_array($key)) {
            $this->__data = array_merge($this->__data, $key);
        } else {
            if ($ignoreDot) {
                $this->__data[$key] = $value;
            } else {
                Util::arraySet($this->__data, $key, $value);
            }
        }
        return $this;
    }

    /**
     * Removes the associated key value from the Element data.
     * @param string $key Key to delete value.
     */
    public function __unset(string $key)
    {
        $this->remove($key);
    }

    /**
     * Removes the associated key value from the Element data.
     * @param string|array $key Key to delete value (accepts dot notation keys). You can also use an array of keys to remove.
     * @param bool $ignoreDot (Optional) Ignore dot notation keys.
     * @return Element Current Element instance for nested calls.
     */
    public function remove($key, bool $ignoreDot = false)
    {
        if (is_array($key)) {
            foreach ((array)$key as $item) $this->remove($item, $ignoreDot);
        } else {
            if (!$ignoreDot) {
                Util::arrayDelete($this->__data, $key);
            } else {
                unset($this->__data[$key]);
            }
        }
        return $this;
    }

    /**
     * Removes all Element data, except the one that matches the specified key.
     * @param string|array $key Key to keep. You can also use an array of keys to keep.
     * @return Element Current Element instance for nested calls.
     */
    public function only($key)
    {
        $this->__data = array_intersect_key($this->__data, array_flip((array)$key));
        return $this;
    }

    /**
     * Checks if any value has been associated to a key in the Element data.
     * @param string $key Key to check.
     * @return bool Returns true or false.
     */
    public function __isset(string $key)
    {
        return $this->has($key);
    }

    /**
     * Checks if any value has been associated to a key in the Element data.
     * @param string|array $key Key to check (accepts dot notation keys). You can also use an array of keys.
     * @return bool Returns true or false.
     */
    public function has($key)
    {
        $result = false;
        foreach ((array)$key as $item) {
            if ($result) break;
            $result = Util::arrayGet($this->__data, $item) !== null;
        }
        return $result;
    }

    /**
     * Deletes all data from the current Element.
     * @return Element Current Element instance for nested calls.
     */
    public function flush()
    {
        $this->__data = [];
        return $this;
    }

    /**
     * Gets the Element data as an associative array.
     * @return array The resulting array.
     */
    public function toArray()
    {
        return $this->__data;
    }

    /**
     * Gets the Element data as a Collection.
     * @return Collection The resulting Collection instance.
     */
    public function toCollection()
    {
        return new Collection($this->__data);
    }

    /**
     * Returns the serializable JSON data for the Element.
     * @return array Element data as an associative array.
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Gets the Element data as JSON.
     * @param int $flags (Optional) JSON encoding flags (same as in `json_encode()` function).
     * @param int $depth (Optional) JSON encoding maximum depth (same as in `json_encode()` function).
     * @return string The resulting JSON string.
     */
    public function toJson(int $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES, int $depth = 512)
    {
        return empty($this->__data) ? '{}' : json_encode($this->__data, $flags, $depth);
    }

    /**
     * Dumps the Element data.
     * @param bool $plain (Optional) Dump data as plain text instead of HTML.
     */
    public function dump(bool $plain = false)
    {
        Util::dump($this, $plain);
    }

    /**
     * Validates the Element data using unique validation rules for each one of the fields.
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
     * Validates the Element data using the same rules for all values.
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
     * Gets the Element data as a string (data will be serialized as JSON).
     * @return string The resulting JSON string.
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * Element debugging information.
     */
    public function __debugInfo()
    {
        return $this->__data;
    }
}
