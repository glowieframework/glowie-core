<?php
    namespace Glowie\Core\Traits;

    use Util;

    /**
     * Generic safe object trait for Glowie application.
     * @category Object
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) Glowie
     * @license MIT
     * @link https://glowie.tk
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
         * @param string $key Key to get value (accepts dot notation keys).
         * @param mixed $default (Optional) Default value to return if the key does not exist.
         * @return mixed Returns the value if exists or the default if not.
         */
        public function get(string $key, $default = null){
            return Util::arrayGet($this->__data, $key, $default);
        }

        /**
         * Sets the value for a key in the Element data.
         * @param string $key Key to set value.
         * @param mixed $value Value to set.
         */
        public function __set(string $key, $value){
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
        public function set($key, $value = null, bool $ignoreDot = false){
            if(is_array($key)){
                $this->__data = array_merge($this->__data, $key);
            }else{
                if($ignoreDot){
                    $this->__data[$key] = $value;
                }else{
                    Util::arraySet($this->__data, $key, $value);
                }
            }
            return $this;
        }

        /**
         * Removes the associated key value from the Element data.
         * @param string $key Key to delete value.
         */
        public function __unset(string $key){
            $this->remove($key);
        }

        /**
         * Removes the associated key value from the Element data.
         * @param string|array $key Key to delete value. You can also use an array of keys to remove.
         * @return Element Current Element instance for nested calls.
         */
        public function remove($key){
            foreach((array)$key as $item){
                if (isset($this->__data[$item])) unset($this->__data[$item]);
            }
            return $this;
        }

        /**
         * Removes all Element data, except the one that matches the specified key.
         * @param string|array $key Key to keep. You can also use an array of keys to keep.
         * @return Element Current Element instance for nested calls.
         */
        public function only($key){
            foreach($this->__data as $field => $value){
                if(!in_array($field, (array)$key)) $this->remove($field);
            }
            return $this;
        }

        /**
         * Checks if any value has been associated to a key in the Element data.
         * @param string $key Key to check.
         * @return bool Returns true or false.
         */
        public function __isset(string $key){
            return $this->has($key);
        }

        /**
         * Checks if any value has been associated to a key in the Element data.
         * @param string|array $key Key to check (accepts dot notation keys). You can also use an array of keys.
         * @return bool Returns true or false.
         */
        public function has($key){
            $result = false;
            foreach((array)$key as $item){
                if($result) break;
                $result = Util::arrayGet($this->__data, $item) !== null;
            }
            return $result;
        }

        /**
         * Deletes all data from the current Element.
         * @return Element Current Element instance for nested calls.
         */
        public function flush(){
            $this->__data = [];
            return $this;
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
        public function toJson(int $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK, int $depth = 512){
            return json_encode($this->__data, $flags, $depth);
        }

        /**
         * Dumps the Element data.
         * @param bool $plain (Optional) Dump data as plain text instead of HTML.
         */
        public function dump(bool $plain = false){
            Util::dump($this, $plain);
        }

        /**
         * Gets the Element data as a string (data will be serialized as JSON).
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