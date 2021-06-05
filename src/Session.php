<?php
    namespace Glowie\Core;

    /**
     * Session manager for Glowie application.
     * @category Session manager
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.0
     */
    class Session{

        /**
         * Starts a new session or resumes the existing one.
         * @param array $data (Optional) Initial data to store in the session.
         */
        public function __construct(array $data = []){
            if(!isset($_SESSION)) session_start();
            if(!empty($data)) $_SESSION = $data;
        }

        /**
         * Gets the value associated to a key in the session.
         * @param mixed $key Key to get value.
         * @return mixed Returns the value if exists or null if there is none.
         */
        public function __get($key){
            if(!isset($_SESSION)) session_start();
            if(isset($_SESSION[$key])){
                return $_SESSION[$key];
            }else{
                return null;
            }
        }

        /**
         * Gets the value associated to a key in the session. If no key is specified, returns\
         * an object with all the session data.
         * @param mixed $key (Optional) Key to get value.
         * @return mixed Returns the value if exists or null if there is none.
         */
        public function get($key = null){
            if(!is_null($key)){
                return $this->__get($key);
            }else{
                if(!isset($_SESSION)) session_start();
                return new Element($_SESSION);
            }
        }

        /**
         * Sets the value for a key in the session.
         * @param mixed $key Key to set value.
         * @param mixed $value Value to set.
         */
        public function __set($key, $value){
            if(!isset($_SESSION)) session_start();
            $_SESSION[$key] = $value;
        }

        /**
         * Sets the value for a key in the session.
         * @param mixed $key Key to set value.
         * @param mixed $value Value to set.
         */
        public function set($key, $value){
            $this->__set($key, $value);
        }

        /**
         * Removes the associated key value in the session.
         * @param mixed $key Key to delete value.
         */
        public function __unset($key){
            if(!isset($_SESSION)) session_start();
            if (isset($_SESSION[$key])) {
                unset($_SESSION[$key]);
            }
        }

         /**
         * Removes the associated key value in the session.
         * @param mixed $key Key to delete value.
         */
        public function remove($key){
            $this->__unset($key);
        }

        /**
         * Checks if any value has been associated to a key in the session.
         * @param mixed $key Key to check.
         * @return bool Returns true or false.
         */
        public function __isset($key){
            if(!isset($_SESSION)) session_start();
            return isset($_SESSION[$key]);
        }

        /**
         * Checks if any value has been associated to a key in the session.
         * @param mixed $key Key to check.
         * @return bool Returns true or false.
         */
        public function has($key){
            return $this->__isset($key);
        }

        /**
         * Deletes all data in the current session.
         */
        public function flush(){
            if(!isset($_SESSION)) session_start();
            $_SESSION = [];
        }

    }

?>