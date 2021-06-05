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
         * Expire time for one minute.
         * @var int
         */
        public const EXPIRES_MINUTE = 60;

        /**
         * Expire time for one hour.
         * @var int
         */
        public const EXPIRES_HOUR = 3600;

        /**
         * Expire time for one day.
         * @var int
         */
        public const EXPIRES_DAY = 86400;

        /**
         * Creates a new instance of the cookie manager.
         * @param array $data (Optional) Initial data to store in the cookies **(default expire time is 1 hour)**.
         */
        public function __construct(array $data = []){
            if(!empty($data)) foreach($data as $key => $value) $this->set($key, $value);
        }

        /**
         * Gets the value associated to a key in the cookies.
         * @param mixed $key Key to get value.
         * @return mixed Returns the value if exists or null if there is none.
         */
        public function __get($key){
            if(isset($_COOKIE[$key])){
                return $_COOKIE[$key];
            }else{
                return null;
            }
        }

        /**
         * Gets the value associated to a key in the cookies. If no key is specified, returns\
         * an object with all the cookies data.
         * @param mixed $key (Optional) Key to get value.
         * @return mixed Returns the value if exists or null if there is none.
         */
        public function get($key = null){
            if(!is_null($key)){
                return $this->__get($key);
            }else{
                return new Element($_COOKIE);
            }
        }

        /**
         * Sets the value for a key in the cookies.
         * @param mixed $key Key to set value.
         * @param mixed $value Value to set.
         */
        public function __set($key, $value){
            $this->set($key, $value);
        }

        /**
         * Sets the value for a key in the cookies.
         * @param mixed $key Key to set value.
         * @param mixed $value Value to set.
         * @param int $expires (Optional) Cookie expiration time in seconds.
         * @param string $path (Optional) Path on the application that the cookie will be available (use `'/'` for the entire application).
         * @param bool $restrict (Optional) Restrict the cookie access only through HTTP protocol.
         */
        public function set($key, $value, int $expires = self::EXPIRES_HOUR, string $path = '/', bool $restrict = false){
            setcookie($key, $value, time() + $expires, $path, '', false, $restrict);
        }

        /**
         * Removes the associated key value in the cookies.
         * @param mixed $key Key to delete value.
         */
        public function __unset($key){
            if (isset($_COOKIE[$key])) {
                $this->set($key, '', -self::EXPIRES_HOUR);
            }
        }

         /**
         * Removes the associated key value in the cookies.
         * @param mixed $key Key to delete value.
         */
        public function remove($key){
            $this->__unset($key);
        }

        /**
         * Checks if any value has been associated to a key in the cookies.
         * @param mixed $key Key to check.
         * @return bool Returns true or false.
         */
        public function __isset($key){
            return isset($_COOKIE[$key]);
        }

        /**
         * Checks if any value has been associated to a key in the cookies.
         * @param mixed $key Key to check.
         * @return bool Returns true or false.
         */
        public function has($key){
            return $this->__isset($key);
        }

        /**
         * Deletes all data in the cookies.
         */
        public function flush(){
            if(!empty($_COOKIE)){
                foreach($_COOKIE as $key => $value) $this->remove($key);
            }
        }

    }

?>