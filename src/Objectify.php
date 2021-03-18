<?php
    namespace Glowie\Core;

    /**
     * Array to object data parser for Glowie application.
     * @category Data parser
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 0.3-alpha
     */
    class Objectify{

        /**
         * Instantiates a new object.
         * @param array $data (Optional) Initial data to parse.
         */
        public function __construct(array $data = []){
            if(!empty($data)){
                foreach($data as $key => $value){
                    $key = $this->parseKey($key);
                    $this->$key = $value;
                }
            }
        }

        /**
         * Gets the value associated to a key in the data.
         * @param string $key Key to get value.
         * @return mixed Returns the value if exists or null if there is none.
         */
        public function __get(string $key){
            if(isset($this->$key)){
                return $this->$key;
            }else{
                return null;
            }
        }

        /**
         * Gets the value associated to a key in the data. If no key is specified, return\
         * an object with all data.
         * @param string $key (Optional) Key to get value.
         * @return mixed Returns the value if exists or null if there is none.
         */
        public function get(string $key = null){
            if(!empty($key)){
                return $this->__get($key);
            }else{
                return $this;
            }
        }

        /**
         * Sets the value for a key in the data.
         * @param string $key Key to set value.
         * @param mixed $value Value to set.
         */
        public function __set(string $key, $value){
            $this->$key = $value;
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
            if (isset($this->$key)) {
                unset($this->$key);
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
            return isset($this->$key);
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
         * Converts the current object to an associative array.
         * @return array The resulting array.
         */
        public function toArray(){
            return get_object_vars($this);
        }

        /**
         * Removes from key all accents and characters that are not valid letters, numbers or underscores.\
         * It also replaces dashes or spaces for underscores and places an underscore before the first character if it is a number.
         * @param string $string Key to be encoded.
         * @return string Encoded key.
         */
        private function parseKey(string $string){
            // Remove accents
            $string = strtr(utf8_decode($string), utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
           
            // Converts spaces and dashes to underscores
            $string = preg_replace('/\s|-/', '_', $string);

            // Removes invalid characters
            $string = preg_replace('/[^a-zA-Z0-9_]/', '', $string);

            // Checks if the first character is a number and add an underscore before it
            $string = preg_replace('/^([0-9])/', '_$1', $string, 1);

            // Returns the encoded key
            return $string;
        }

    }

?>