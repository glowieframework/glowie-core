<?php
    namespace Glowie\Core;

    /**
     * Cookie manager for Glowie application.
     * @category Cookie manager
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.0
     */
    class Cookies{

        /**
         * Expire time for 1 minute.
         * @var int
         */
        public const EXPIRES_MINUTE = 60;

        /**
         * Expire time for 1 hour.
         * @var int
         */
        public const EXPIRES_HOUR = 3600;

        /**
         * Expire time for 1 day.
         * @var int
         */
        public const EXPIRES_DAY = 86400;

        /**
         * Creates a new instance of the cookie manager.
         * @param array $data (Optional) An associative array with the initial data to store in the cookies\
         * **(default expire time is 1 hour)**.
         */
        public function __construct(array $data = []){
            if(!empty($data)) foreach($data as $key => $value) $this->set($key, $value);
        }

        /**
         * Gets the value associated to a key in the cookies data.
         * @param string $key Key to get value.
         * @return mixed Returns the value if exists or null if there is none.
         */
        public function __get(string $key){
            return $_COOKIE[$key] ?? null;
        }

        /**
         * Gets the value associated to a key in the cookies data.
         * @param string $key Key to get value.
         * @return mixed Returns the value if exists or null if there is none.
         */
        public function get(string $key){
            return $this->__get($key);
        }

        /**
         * Sets the value for a key in the cookies data.
         * @param string $key Key to set value.
         * @param mixed $value Value to set.
         */
        public function __set(string $key, $value){
            $this->set($key, $value);
        }

        /**
         * Sets the value for a key in the cookies.
         * @param string $key Key to set value.
         * @param mixed $value Value to set.
         * @param int $expires (Optional) Cookie expiration time in seconds.
         * @param string $path (Optional) Path on the application that the cookie will be available (use `'/'` for the entire application).
         * @param bool $restrict (Optional) Restrict the cookie access only through HTTP protocol.
         */
        public function set(string $key, $value, int $expires = self::EXPIRES_HOUR, string $path = '/', bool $restrict = false){
            $_COOKIE[$key] = $value;
            setcookie($key, $value, time() + $expires, $path, '', false, $restrict);
        }

        /**
         * Removes the associated key value from the cookies data.
         * @param string $key Key to delete value.
         */
        public function __unset(string $key){
            if (isset($_COOKIE[$key])) {
                $this->set($key, null, -self::EXPIRES_HOUR);
            }
        }

         /**
         * Removes the associated key value from the cookies data.
         * @param string $key Key to delete value.
         */
        public function remove(string $key){
            $this->__unset($key);
        }

        /**
         * Checks if any value has been associated to a key in the cookies data.
         * @param string $key Key to check.
         * @return bool Returns true or false.
         */
        public function __isset(string $key){
            return isset($_COOKIE[$key]);
        }

        /**
         * Checks if any value has been associated to a key in the cookies data.
         * @param string $key Key to check.
         * @return bool Returns true or false.
         */
        public function has(string $key){
            return $this->__isset($key);
        }

        /**
         * Deletes all data from the cookies.
         */
        public function flush(){
            if(!empty($_COOKIE)){
                foreach($_COOKIE as $key => $value) $this->remove($key);
            }
        }

        /**
         * Gets the cookies data as an associative array.
         * @return array The resulting array.
         */
        public function toArray(){
            return $_COOKIE;
        }

    }

?>