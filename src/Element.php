<?php
    namespace Glowie\Core;

    /**
     * Generic safe object instance for Glowie application.
     * @category Object
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 0.3-alpha
     */
    class Element{

        /**
         * Element data.
         * @var array
         */
        protected $_data = [];

        /**
         * Instantiates a new Element.
         * @param array $data (Optional) Initial data to parse.
         */
        public function __construct(array $data = []){
            if(!empty($data)) $this->_data = $data;
        }

        /**
         * Gets the value associated to a key in the data.
         * @param string $key Key to get value.
         * @return mixed Returns the value if exists or null if there is none.
         */
        public function __get(string $key){
            if(isset($this->_data[$key])){
                return $this->_data[$key];
            }else{
                return null;
            }
        }

        /**
         * Gets the value associated to a key in the data.
         * @param string $key (Optional) Key to get value.
         * @return mixed Returns the value if exists or null if there is none.
         */
        public function get(string $key){
            return $this->__get($key);
        }

        /**
         * Sets the value for a key in the data.
         * @param string $key Key to set value.
         * @param mixed $value Value to set.
         */
        public function __set(string $key, $value){
            $this->_data[$key] = $value;
        }

        /**
         * Sets the value for a key in the data.
         * @param string $key Key to set value.
         * @param mixed $value Value to set.
         */
        public function set(string $key, $value){
            $this->__set($key, $value);
        }

        /**
         * Removes the associated key value in the data.
         * @param string $key Key to delete value.
         */
        public function __unset(string $key){
            if (isset($this->_data[$key])) {
                unset($this->_data[$key]);
            }
        }

        /**
         * Removes the associated key value in the session.
         * @param string $key Key to delete value.
         */
        public function remove(string $key){
            $this->__unset($key);
        }

        /**
         * Checks if any value has been associated to a key in the data.
         * @param string $key Key to check.
         * @return bool Returns true or false.
         */
        public function __isset(string $key){
            return isset($this->_data[$key]);
        }

        /**
         * Checks if any value has been associated to a key in the data.
         * @param string $key Key to check.
         * @return bool Returns true or false.
         */
        public function has(string $key){
            return $this->__isset($key);
        }

        /**
         * Gets the Element data as an associative array.
         * @return array The resulting array.
         */
        public function toArray(){
            return $this->_data;
        }

    }

?>