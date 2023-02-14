<?php
    namespace Glowie\Core\Tools;

    use Config;
    use Util;
    use SQLite3;
    use Exception;

    /**
     * Cache for Glowie application.
     * @category Cache
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) Glowie
     * @license MIT
     * @link https://glowie.tk
     */
    class Cache{

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
         * Current database instance.
         * @var SQLite3
         */
        private static $db = null;

        /**
         * Creates a new Cache handler instance.
         */
        public function __construct(){
            // Checks if the cache database is already connected
            if(!self::$db){
                // Checks for sqlite3 extension
                if(!extension_loaded('sqlite3')) throw new Exception('Cache: Missing "sqlite3" extension in your PHP installation');

                // Create the connection
                self::$db = new SQLite3(Config::get('cache.path', Util::location('storage/cache/cache.tmp')));

                // Creates the cache table if not exists yet
                $tableExists = self::$db->querySingle("SELECT name FROM sqlite_master WHERE type='table' AND name='cache'");
                if(!$tableExists) self::$db->exec("CREATE TABLE cache(key TEXT PRIMARY KEY, value TEXT, expires INTEGER)");
            }
        }

        /**
         * Gets a cache variable.
         * @param string $key Key to get value.
         * @param mixed $default (Optional) Default value to return if the key does not exist.
         * @return mixed Returns the value if exists or the default if not.
         */
        public function get(string $key, $default = null){
            // Escape key
            $key = self::$db->escapeString($key);

            // Calculate expire date
            $expires = time();

            // Return result
            return self::$db->querySingle("SELECT value FROM cache WHERE key = '{$key}' AND (expires IS NULL OR expires >= {$expires})") ?? $default;
        }

        /**
         * Gets a cache variable.
         * @param string $key Key to get value.
         * @return mixed Returns the value if exists or null if there is none.
         */
        public function __get(string $key){
            return $this->get($key);
        }

        /**
         * Sets a cache variable.
         * @param string $key Key to set value.
         * @param mixed $value Value to set. It will be casted to a string.
         * @param int|null $expires (Optional) Expiration time in seconds.
         * @return Cache Current instance for nested calls.
         */
        public function set(string $key, $value, ?int $expires = null){
            // Escape key and values
            $key = self::$db->escapeString($key);
            if(is_null($value)){
                $value = 'NULL';
            }else{
                $value = "'" . self::$db->escapeString((string)$value) . "'";
            }

            // Calculate expire date
            $expires = $expires ? time() + $expires : 'NULL';

            // Inserts the row
            self::$db->exec("REPLACE INTO cache(key, value, expires) VALUES('{$key}', {$value}, {$expires})");
            return $this;
        }

        /**
         * Increments an existing cache variable.
         * @param string $key Key to increment value.
         * @param int $amount (Optional) Amount to increment.
         * @return Cache Current instance for nested calls.
         */
        public function increment(string $key, int $amount = 1){
            // Escape key
            $key = self::$db->escapeString($key);

            // Updates the row
            self::$db->exec("UPDATE cache SET value = value + {$amount} WHERE key = '{$key}'");
            return $this;
        }

        /**
         * Decrements an existing cache variable.
         * @param string $key Key to increment value.
         * @param int $amount (Optional) Amount to increment.
         * @return Cache Current instance for nested calls.
         */
        public function decrement(string $key, int $amount = 1){
            // Escape key
            $key = self::$db->escapeString($key);

            // Updates the row
            self::$db->exec("UPDATE cache SET value = value - {$amount} WHERE key = '{$key}'");
            return $this;
        }

        /**
         * Sets a cache variable.
         * @param string $key Key to set value.
         * @param mixed $value Value to set. It will be casted to a string.
         */
        public function __set(string $key, $value){
            $this->set($key, $value);
        }

        /**
         * Checks if any value has been associated to a key in the cache.
         * @param string $key Key to check.
         * @return bool Returns true or false.
         */
        public function has(string $key){
            // Escape key
            $key = self::$db->escapeString($key);

            // Calculate expire date
            $expires = time();

            // Returns result
            return self::$db->querySingle("SELECT COUNT(key) FROM cache WHERE key = '{$key}' AND (expires IS NULL OR expires >= {$expires})") != 0;
        }

        /**
         * Checks if any value has been associated to a key in the cache.
         * @param string $key Key to check.
         * @return bool Returns true or false.
         */
        public function __isset(string $key){
            return $this->has($key);
        }

        /**
         * Removes the associated key value from the cache.
         * @param string|array Key to delete value. You can also use an array of keys to remove.
         * @return Cache Current instance for nested calls.
         */
        public function remove($key){
            // Escape keys
            $keys = [];
            foreach((array)$key as $item) $keys[] = "'" . self::$db->escapeString($item) . "'";
            $keys = implode(', ', $keys);

            // Remove rows
            self::$db->exec("DELETE FROM cache WHERE key IN ({$keys})");
            return $this;
        }

         /**
         * Removes the associated key value from the cookies data.
         * @param string $key Key to delete value.
         */
        public function __unset(string $key){
            $this->remove($key);
        }

        /**
         * Purge expired variables from the cache.
         * @return Cache Current instance for nested calls.
         */
        public function purge(){
            // Calculate expire date
            $expires = time();

            // Purge data
            self::$db->exec("DELETE FROM cache WHERE expires < '{$expires}'");
            return $this;
        }

        /**
         * Delete all data from the cache.
         * @return Cache Current instance for nested calls.
         */
        public function flush(){
            self::$db->exec("DELETE FROM cache");
            return $this;
        }

    }

?>