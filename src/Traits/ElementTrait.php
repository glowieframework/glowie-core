<?php
    namespace Glowie\Core\Traits;

    /**
     * Generic safe object trait for Glowie application.
     * @category Object
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.0
     */
    trait ElementTrait{

        /**
         * Element data.
         * @var array
         */
        private $__data = [];

        /**
         * Constructs the Element trait data.
         * @param array $data (Optional) An associative array with the initial data to parse.
         */
        private function __constructTrait(array $data = []){
            if(!empty($data)) $this->__data = $data;
        }

        /**
         * Gets the value associated to a key in the Element data.
         * @param string $key Key to get value.
         * @return mixed Returns the value if exists or null if there is none.
         */
        public function __get(string $key){
            return $this->get($key);
        }

        /**
         * Gets the value associated to a key in the Element data.
         * @param string $key Key to get value.
         * @param mixed $default (Optional) Default value to return if the key does not exist.
         * @return mixed Returns the value if exists or the default if not.
         */
        public function get(string $key, $default = null){
            return $this->__data[$key] ?? $default;
        }

        /**
         * Sets the value for a key in the Element data.
         * @param string $key Key to set value.
         * @param mixed $value Value to set.
         */
        public function __set(string $key, $value){
            $this->__data[$key] = $value;
        }

        /**
         * Sets the value for a key in the Element data.
         * @param string $key Key to set value.
         * @param mixed $value Value to set.
         */
        public function set(string $key, $value){
            $this->__set($key, $value);
        }

        /**
         * Removes the associated key value from the Element data.
         * @param string $key Key to delete value.
         */
        public function __unset(string $key){
            if (isset($this->__data[$key])) {
                unset($this->__data[$key]);
            }
        }

        /**
         * Removes the associated key value from the Element data.
         * @param string $key Key to delete value.
         */
        public function remove(string $key){
            $this->__unset($key);
        }

        /**
         * Checks if any value has been associated to a key in the Element data.
         * @param string $key Key to check.
         * @return bool Returns true or false.
         */
        public function __isset(string $key){
            return isset($this->__data[$key]);
        }

        /**
         * Checks if any value has been associated to a key in the Element data.
         * @param string $key Key to check.
         * @return bool Returns true or false.
         */
        public function has(string $key){
            return $this->__isset($key);
        }

        /**
         * Deletes all data from the current Element.
         */
        public function flush(){
            $this->__data = [];
        }

        /**
         * Gets the Element data as an associative array.
         * @return array The resulting array.
         */
        public function toArray(){
            return $this->__data;
        }

        /**
         * Gets the Element data as JSON.
         * @param int $flags (Optional) JSON encoding flags (same as in `json_encode()` function).
         * @param int $depth (Optional) JSON encoding maximum depth (same as in `json_encode()` function).
         * @return string The resulting JSON string.
         */
        public function toJson(int $flags = 0, int $depth = 512){
            return json_encode($this->__data, $flags, $depth);
        }

        /**
         * Gets the Element data as JSON.
         * @return string The resulting JSON string.
         */
        public function __toString(){
            return $this->toJson();
        }

        /**
         * Element debugging information.
         */
        public function __debugInfo(){
            return $this->__data;
        }

    }

?>