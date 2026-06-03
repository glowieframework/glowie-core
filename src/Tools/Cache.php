<?php

namespace Glowie\Core\Tools;

use Config;
use Util;
use Exception;
use JsonSerializable;
use Glowie\Core\Collection;
use Glowie\Core\Database\Kraken;

/**
 * Cache for Glowie application.
 * @category Cache
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 */
class Cache implements JsonSerializable
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
     * Current database instance.
     * @var Kraken|null
     */
    private static $db = null;

    /**
     * Cache table name.
     * @var string
     */
    private static $table;

    /**
     * Validator instance.
     * @var Validator
     */
    private $__validator;

    /**
     * Cache instance.
     * @var Cache|null
     */
    private static $instance;

    /**
     * Creates a new Cache handler instance.
     * @param array $data (Optional) Initial data to fill into the cache.
     */
    public function __construct(array $data = [])
    {
        // Checks if the cache database is already connected
        if (!self::$db) {
            // Sets the table name
            self::$table = Config::get('cache.table', 'cache');

            // Checks for cache driver
            $driver = Config::get('cache.driver', 'file');

            // Connects to the driver
            if ($driver === 'file') {
                self::$db = $this->createFileConnection();
            } else if ($driver === 'database') {
                $connection = Config::get('cache.connection', 'default');
                self::$db = new Kraken(self::$table, $connection);
            } else {
                throw new Exception("Cache: Unsupported driver: \"$driver\"");
            }
        }

        // Parse initial data, if any
        if (!empty($data)) $this->set($data);
    }

    /**
     * Creates the SQLite connection for the cache file driver.
     * @return Kraken Returns the database instance.
     */
    private function createFileConnection()
    {
        // Sets the connection info
        $connection = 'sqlite_cache_driver';
        Config::set("database.$connection", [
            'driver' => 'sqlite',
            'path' => Config::get('cache.path', Util::location('storage/cache/cache.db'))
        ]);

        // Creates the connection
        $db = new Kraken(self::$table, $connection);

        // Creates the cache table if not exists yet
        $tableExists = $db->select('name')
            ->from('sqlite_master')
            ->where('type', 'table')
            ->where('name', self::$table)
            ->fetchRow();

        if (empty($tableExists)) {
            $table = $db->escapeIdentifier(self::$table);
            $db->query("CREATE TABLE $table (`key` TEXT PRIMARY KEY, `value` BLOB, `expires` INTEGER)");
        }

        // Returns the connection
        return $db;
    }

    /**
     * Creates a new Cache handler instance in a static-binding or returns the existing one.
     * @param array $data (Optional) Initial data to fill the cache.
     * @return Cache Returns the Cache instance.
     */
    public static function make(array $data = [])
    {
        if (!self::$instance) self::$instance = new static($data);
        return self::$instance;
    }

    /**
     * Gets a cache variable.
     * @param string $key Key to get value.
     * @param mixed $default (Optional) Default value to return if the key does not exist.
     * @return mixed Returns the unserialized value if exists or the default if not.
     */
    public function get(string $key, $default = null)
    {
        // Gets the cache value
        $result = self::$db->select('value')
            ->from(self::$table)
            ->where('key', $key)
            ->where(function (Kraken $q) {
                $q->whereNull('expires');
                $q->orWhere('expires', '>=', time());
            })
            ->asElement()
            ->fetchRow();

        // Return result
        if (empty($result) || is_null($result->value)) return $default;
        return unserialize($result->value);
    }

    /**
     * Gets the expiration time of a cache variable.
     * @param string $key Key to get expiration time.
     * @return int|null Returns the expiration timestamp if exists or null if not.
     */
    public function getExpiration(string $key)
    {
        // Gets the cache expiration
        $result = self::$db->select('expires')
            ->from(self::$table)
            ->where('key', $key)
            ->where(function (Kraken $q) {
                $q->whereNull('expires');
                $q->orWhere('expires', '>=', time());
            })
            ->asElement()
            ->fetchRow();

        // Return result
        return !empty($result) ? (int)$result->expires : null;
    }

    /**
     * Gets a cache variable.
     * @param string $key Key to get value.
     * @return mixed Returns the unserialized value if exists or null if there is none.
     */
    public function __get(string $key)
    {
        return $this->get($key);
    }

    /**
     * Sets a cache variable.
     * @param string|array $key Key to set value. You can also pass an associative array of values to set at once and they will be merged into the cache.
     * @param mixed $value (Optional) Value to set. It will be serialized.
     * @param int|null $expires (Optional) Expiration time in seconds. To never expire, use `null`.
     * @return bool Returns true on success, false on failure.
     */
    public function set($key, $value = null, ?int $expires = self::EXPIRES_NEVER)
    {
        // Sets an array of values
        if (is_array($key)) {
            foreach ($key as $field => $val) {
                $this->set($field, $val, $expires);
            }
            return true;
        }

        // Calculate expire date
        $expires = $expires ? (time() + $expires) : null;

        // Inserts the row
        return self::$db->table(self::$table)->replace([
            'key' => $key,
            'value' => !is_null($value) ? serialize($value) : null,
            'expires' => $expires
        ]);
    }

    /**
     * Increments an existing numeric cache variable.
     * @param string $key Key to increment value.
     * @param int|float $amount (Optional) Amount to increment.
     * @return bool Returns true on success, false on failure.
     */
    public function increment(string $key, $amount = 1)
    {
        // Gets the current value, if any
        $value = $this->get($key);
        if (is_null($value) || !is_numeric($value)) return false;

        // Updates the row
        return self::$db->table(self::$table)->where('key', $key)->update([
            'value' => serialize($value + $amount)
        ]);
    }

    /**
     * Decrements an existing numeric cache variable.
     * @param string $key Key to increment value.
     * @param int|float $amount (Optional) Amount to increment.
     * @return bool Returns true on success, false on failure.
     */
    public function decrement(string $key, $amount = 1)
    {
        // Gets the current value, if any
        $value = $this->get($key);
        if (is_null($value) || !is_numeric($value)) return false;

        // Updates the row
        return self::$db->table(self::$table)->where('key', $key)->update([
            'value' => serialize($value - $amount)
        ]);
    }

    /**
     * Sets a cache variable.
     * @param string $key Key to set value.
     * @param mixed $value Value to set. It will be serialized.
     */
    public function __set(string $key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Checks if any value has been associated to a key in the cache.
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
     * Checks if any key value is missing in the cache.
     * @param string|array $key Key to check. You can also use an array of keys.
     * @return bool Returns true or false.
     */
    public function missing($key)
    {
        $result = false;
        foreach ((array)$key as $item) {
            if ($result) break;
            $result = !$this->__isset($item);
        }
        return $result;
    }

    /**
     * Checks if any value has been associated to a key in the cache.
     * @param string $key Key to check.
     * @return bool Returns true or false.
     */
    public function __isset(string $key)
    {
        return self::$db->table(self::$table)
            ->where('key', $key)
            ->where(function (Kraken $q) {
                $q->whereNull('expires');
                $q->orWhere('expires', '>=', time());
            })
            ->exists();
    }

    /**
     * Removes the associated key value from the cache.
     * @param string|array $key Key to delete value. You can also use an array of keys to remove.
     * @return Cache Current instance for nested calls.
     */
    public function remove($key)
    {
        self::$db->table(self::$table)->whereIn('key', (array)$key)->delete();
        return $this;
    }

    /**
     * Removes all cache data, except the one that matches the specified key.
     * @param string|array $key Key to keep. You can also use an array of keys to keep.
     * @return Cache Current instance for nested calls.
     */
    public function only($key)
    {
        foreach (array_keys($this->toArray()) as $field) {
            if (!in_array($field, (array)$key)) $this->remove($field);
        }
        return $this;
    }

    /**
     * Removes the associated key value from the cache data.
     * @param string $key Key to delete value.
     */
    public function __unset(string $key)
    {
        $this->remove($key);
    }

    /**
     * Purge expired variables from the cache.
     * @return bool Returns true on success, false on failure.
     */
    public function purge()
    {
        return self::$db->table(self::$table)->where('expires', '<', time())->delete();
    }

    /**
     * Delete all data from the cache.
     * @return bool Returns true on success, false on failure.
     */
    public function flush()
    {
        return self::$db->table(self::$table)->withoutSafeUpdateDeletes()->delete();
    }

    /**
     * Gets the cache data as an associative array.
     * @return array The resulting array.
     */
    public function toArray()
    {
        return self::$db->table(self::$table)
            ->where(function (Kraken $q) {
                $q->whereNull('expires');
                $q->orWhere('expires', '>=', time());
            })
            ->asArray()
            ->fetchAll()
            ->column('value', 'key')
            ->map(function ($value) {
                return !is_null($value) ? unserialize($value) : null;
            });
    }

    /**
     * Gets the cache data as a Collection.
     * @return Collection Returns the cache data as a Collection.
     */
    public function toCollection()
    {
        return new Collection($this->toArray());
    }

    /**
     * Returns the serializable JSON data for the cache.
     * @return array Cache data as an associative array.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Gets the cache data as JSON.
     * @param int $flags (Optional) JSON encoding flags (same as in `json_encode()` function).
     * @param int $depth (Optional) JSON encoding maximum depth (same as in `json_encode()` function).
     * @return string The resulting JSON string.
     */
    public function toJson(int $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES, int $depth = 512)
    {
        $data = $this->toArray();
        return empty($data) ? '{}' : json_encode($data, $flags, $depth);
    }

    /**
     * Dumps the cache data.
     */
    public function dump()
    {
        Util::dump($this);
    }

    /**
     * Validates the cache data using unique validation rules for each one of the fields.
     * @param array $rules Associative array with validation rules for each field.
     * @param bool $bail (Optional) Stop validation of each field after first failure found.
     * @param bool $bailAll (Optional) Stop validation of all fields after first failure found.
     * @param array $customMessages (Optional) An associative array with the custom validation error messages.
     * @return bool Returns true if all rules passed for all fields, false otherwise.
     */
    public function validate(array $rules, bool $bail = true, bool $bailAll = true, array $customMessages = [])
    {
        return $this->getValidator()->validateFields($this->toArray(), $rules, $bail, $bailAll, $customMessages);
    }

    /**
     * Validates the cache data using the same rules for all values.
     * @param string|array $rules Validation rules for the data. Can be a single rule or an array of rules.
     * @param bool $bail (Optional) Stop validation of each value after first failure found.
     * @param bool $bailAll (Optional) Stop validation of all values after first failure found.
     * @return bool Returns true if all rules passed for all values, false otherwise.
     */
    public function validateAll($rules, bool $bail = true, bool $bailAll = true)
    {
        return $this->getValidator()->validateMultiple($this->toArray(), $rules, $bail, $bailAll);
    }

    /**
     * Gets the Validator instance associated with the data.
     * @return Validator The validator instance.
     */
    public function getValidator()
    {
        if (!$this->__validator) $this->__validator = new Validator();
        return $this->__validator;
    }

    /**
     * Gets the cache data as a string (data will be serialized as JSON).
     * @return string The resulting JSON string.
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * Cache debugging information.
     */
    public function __debugInfo()
    {
        return $this->toArray();
    }
}
