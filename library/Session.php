<?php

    /**
     * Session manager for Glowie application.
     * @category Session manager
     * @package glowie
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 0.2-alpha
     */
    class Session{

        /**
         * Instantiates a new session or resumes the existing one.
         */
        public function __construct(){
            if(!isset($_SESSION)) session_start();
        }

        /**
         * Gets the value associated to a key in the session.
         * @param string $key Key to get value.
         * @return mixed Returns the value if exists or null if there is none.
         */
        public function __get(string $key){
            if(isset($_SESSION[$key])){
                return $_SESSION[$key];
            }else{
                return null;
            }
        }

        /**
         * Sets the value for a key in the session.
         * @param string $key Key to set value.
         * @param mixed $value Value to set.
         */
        public function __set(string $key, $value){
            $_SESSION[$key] = $value;
        }

        /**
         * Deletes the associated key value in the session.
         * @param string $key Key to delete value.
         */
        public function __unset(string $key){
            if (isset($_SESSION[$key])) {
                unset($_SESSION[$key]);
            }
        }

        /**
         * Checks if any value has been associated to a key in the session.
         * @param string $key Key to check.
         * @return bool Returns true or false.
         */
        public function __isset(string $key){
            return isset($_SESSION[$key]);
        }

        /**
         * Destroys the current session.
         */
        public function destroy(){
            if(isset($_SESSION)) session_destroy();
        }

    }

?>