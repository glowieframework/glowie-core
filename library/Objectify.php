<?php

    /**
     * Array to object data parser for Glowie application.
     * @category Data parser
     * @package glowie
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 0.2-alpha
     */
    class Objectify{

        /**
         * Instantiates a new object.
         * @param array $data (Optional) Initial data to parse.
         * @param bool $toLower (Optional) Convert keys to lowercase.
         */
        public function __construct(array $data = [], bool $toLower = false){
            if(!empty($data)){
                foreach($data as $key => $value){
                    $key = $this->parseKey($key, $toLower);
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
         * Sets the value for a key in the data.
         * @param string $key Key to set value.
         * @param mixed $value Value to set.
         */
        public function __set(string $key, $value){
            $this->$key = $value;
        }

        /**
         * Deletes the associated key value in the data.
         * @param string $key Key to delete value.
         */
        public function __unset(string $key){
            if (isset($this->$key)) {
                unset($this->$key);
            }
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
         * @param bool $lowercase (Optional) Determines if all characters must be lowercased.
         * @return string Encoded key.
         */
        private function parseKey(string $string, bool $lowercase = false){
            // Remove accents
            $string = strtr(utf8_decode($string), utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
           
            // Converts spaces and dashes to underscores
            $string = preg_replace('/\s|-/', '_', $string);

            // Removes invalid characters
            $string = preg_replace('/[^a-zA-Z0-9_]/', '', $string);

            // Checks if the first character is a number and add an underscore before it
            $string = preg_replace('/^([0-9])/', '_$1', $string, 1);

            // Returns the encoded key lowercased or not
            if ($lowercase) {
                return strtolower($string);
            } else {
                return $string;
            }
        }

    }

?>