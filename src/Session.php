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
     * @version 0.3-alpha
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
            if(!isset($_SESSION)) trigger_error('Session: session was not started properly');
            if(isset($_SESSION[$key])){
                return $_SESSION[$key];
            }else{
                return null;
            }
        }

        /**
         * Gets the value associated to a key in the session. If no key is specified, return\
         * an object with all the session data.
         * @param string $key (Optional) Key to get value.
         * @return mixed Returns the value if exists or null if there is none.
         */
        public function get(string $key = null){
            if(!isset($_SESSION)) trigger_error('Session: session was not started properly');
            if(!empty($key)){
                return $this->__get($key);
            }else{
                return new Objectify($_SESSION);
            }
        }

        /**
         * Sets the value for a key in the session.
         * @param string $key Key to set value.
         * @param mixed $value Value to set.
         */
        public function __set(string $key, $value){
            if(!isset($_SESSION)) trigger_error('Session: session was not started properly');
            $_SESSION[$key] = $value;
        }

        /**
         * Sets the value for a key in the session.
         * @param string $key Key to set value.
         * @param mixed $value Value to set.
         */
        public function set(string $key, $value){
            $this->__set($key, $value);
        }

        /**
         * Removes the associated key value in the session.
         * @param string $key Key to delete value.
         */
        public function __unset(string $key){
            if(!isset($_SESSION)) trigger_error('Session: session was not started properly');
            if (isset($_SESSION[$key])) {
                unset($_SESSION[$key]);
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
         * Checks if any value has been associated to a key in the session.
         * @param string $key Key to check.
         * @return bool Returns true or false.
         */
        public function __isset(string $key){
            if(!isset($_SESSION)) trigger_error('Session: session was not started properly');
            return isset($_SESSION[$key]);
        }

        /**
         * Checks if any value has been associated to a key in the session.
         * @param string $key Key to check.
         * @return bool Returns true or false.
         */
        public function has(string $key){
            return $this->__isset($key);
        }

        /**
         * Destroys the current session. You must start a new one\
         * if you want to use session data again.
         */
        public function destroy(){
            if(isset($_SESSION)) session_destroy();
        }

    }

?>