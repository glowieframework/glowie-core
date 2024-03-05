<?php
    namespace Glowie\Core\Tools;

    use Config;
    use Util;
    use SQLite3;
    use Exception;
    use JsonSerializable;
    use Glowie\Core\Collection;

    /**
     * Cache for Glowie application.
     * @category Cache
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) Glowie
     * @license MIT
     * @link https://gabrielsilva.dev.br/glowie
     */
    class Cache implements JsonSerializable{

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
        public function __construct(array $data = []){
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

            // Parse initial data, if any
            if(!empty($data)) $this->set($data);
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
         * @param string|array $key Key to set value. You can also pass an associative array\
         * of values to set at once and they will be merged into the cache.
         * @param mixed $value (Optional) Value to set. It will be casted to a string.
         * @param int|null $expires (Optional) Expiration time in seconds.
         * @return Cache Current instance for nested calls.
         */
        public function set($key, $value = null, ?int $expires = null){
            if(is_array($key)){
                foreach($key as $field => $value) $this->set($field, $value, $expires);
            }else{
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
            }
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
         * @param string|array $key Key to check. You can also use an array of keys.
         * @return bool Returns true or false.
         */
        public function has($key){
            $result = false;
            foreach((array)$key as $item){
                if($result) break;
                $result = $this->__isset($item);
            }
            return $result;
        }

        /**
         * Checks if any value has been associated to a key in the cache.
         * @param string $key Key to check.
         * @return bool Returns true or false.
         */
        public function __isset(string $key){
            // Escape key
            $key = self::$db->escapeString($key);

            // Calculate expire date
            $expires = time();

            // Returns result
            return self::$db->querySingle("SELECT COUNT(key) FROM cache WHERE key = '{$key}' AND (expires IS NULL OR expires >= {$expires})") != 0;
        }

        /**
         * Removes the associated key value from the cache.
         * @param string|array $key Key to delete value. You can also use an array of keys to remove.
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
         * Removes all cache data, except the one that matches the specified key.
         * @param string|array $key Key to keep. You can also use an array of keys to keep.
         * @return Cache Current instance for nested calls.
         */
        public function only($key){
            foreach($this->toArray() as $field => $value){
                if(!in_array($field, (array)$key)) $this->remove($field);
            }
            return $this;
        }

         /**
         * Removes the associated key value from the cache data.
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

        /**
         * Gets the cache data as an associative array.
         * @return array The resulting array.
         */
        public function toArray(){
            // Calculate expire date
            $expires = time();

            // Get data
            $result = self::$db->query("SELECT key, value FROM cache WHERE (expires IS NULL OR expires >= {$expires})");

            // Parse resulting rows
            $return = [];
            while($row = $result->fetchArray(SQLITE3_ASSOC)) $return[] = $row;
            return array_combine(array_column($return, 'key'), array_column($return, 'value'));
        }

        /**
         * Gets the cache data as a Collection.
         * @return Collection Returns the cache data as a Collection.
         */
        public function toCollection(){
            return new Collection($this->toArray());
        }

        /**
         * Returns the serializable JSON data for the cache.
         * @return array Cache data as an associative array.
         */
        public function jsonSerialize(){
            return $this->toArray();
        }

        /**
         * Gets the cache data as JSON.
         * @param int $flags (Optional) JSON encoding flags (same as in `json_encode()` function).
         * @param int $depth (Optional) JSON encoding maximum depth (same as in `json_encode()` function).
         * @return string The resulting JSON string.
         */
        public function toJson(int $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK, int $depth = 512){
            $data = $this->toArray();
            return empty($data) ? '{}' : json_encode($data, $flags, $depth);
        }

        /**
         * Dumps the cache data.
         * @param bool $plain (Optional) Dump data as plain text instead of HTML.
         */
        public function dump(bool $plain = false){
            Util::dump($this, $plain);
        }

        /**
         * Gets the cache data as a string (data will be serialized as JSON).
         * @return string The resulting JSON string.
         */
        public function __toString(){
            return $this->toJson();
        }

        /**
         * Cache debugging information.
         */
        public function __debugInfo(){
            return $this->toArray();
        }

    }

?>