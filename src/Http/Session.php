<?php
    namespace Glowie\Core\Http;

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
         * Session flash data.
         * @var array
         */
        private static $flash = [];

        /**
         * Starts a new session or resumes the existing one.
         * @param array $data (Optional) An associative array with the initial data to store in the session.
         */
        public function __construct(array $data = []){
            if(!isset($_SESSION)) session_start();
            if(!empty($data)) $_SESSION = $data;
            self::$flash = $this->get('FLASH_DATA') ?? [];
        }

        /**
         * Registers the session save path.
         */
        public static function register(){
            $sessdir = '../storage/session';
            if(!is_writable($sessdir)) trigger_error('Session: Directory "app/storage/session" is not writable, please check your chmod settings', E_USER_ERROR);
            session_save_path($sessdir);
            ini_set('session.gc_probability', 1);
        }

        /**
         * Gets the value associated to a key in the session data.
         * @param string $key Key to get value.
         * @return mixed Returns the value if exists or null if there is none.
         */
        public function __get(string $key){
            if(!isset($_SESSION)) session_start();
            return $_SESSION[$key] ?? null;
        }

        /**
         * Gets the value associated to a key in the session data.
         * @param string $key Key to get value.
         * @return mixed Returns the value if exists or null if there is none.
         */
        public function get(string $key){
            return $this->__get($key);
        }

        /**
         * Sets the value for a key in the session data.
         * @param string $key Key to set value.
         * @param mixed $value Value to set.
         */
        public function __set(string $key, $value){
            if(!isset($_SESSION)) session_start();
            $_SESSION[$key] = $value;
        }

        /**
         * Sets the value for a key in the session data.
         * @param string $key Key to set value.
         * @param mixed $value Value to set.
         */
        public function set(string $key, $value){
            $this->__set($key, $value);
        }

        /**
         * Removes the associated key value from the session data.
         * @param string $key Key to delete value.
         */
        public function __unset(string $key){
            if(!isset($_SESSION)) session_start();
            if (isset($_SESSION[$key])) {
                unset($_SESSION[$key]);
            }
        }

         /**
         * Removes the associated key value from the session data.
         * @param string $key Key to delete value.
         */
        public function remove(string $key){
            $this->__unset($key);
        }

        /**
         * Checks if any value has been associated to a key in the session data.
         * @param string $key Key to check.
         * @return bool Returns true or false.
         */
        public function __isset(string $key){
            if(!isset($_SESSION)) session_start();
            return isset($_SESSION[$key]);
        }

        /**
         * Checks if any value has been associated to a key in the session data.
         * @param string $key Key to check.
         * @return bool Returns true or false.
         */
        public function has(string $key){
            return $this->__isset($key);
        }

        /**
         * Deletes all data from the session.
         */
        public function flush(){
            if(!isset($_SESSION)) session_start();
            $_SESSION = [];
            self::$flash = [];
        }

        /**
         * Gets the session data as an associative array.
         * @return array The resulting array.
         */
        public function toArray(){
            if(!isset($_SESSION)) session_start();
            return $_SESSION;
        }

        /**
         * Sets the value for a key in the session flash data.
         * @param string $key Key to set value.
         * @param mixed $value Value to set.
         */
        public function setFlash(string $key, $data){
            self::$flash = $this->get('FLASH_DATA') ?? [];
            self::$flash[$key] = $data;
            $this->set('FLASH_DATA', self::$flash);
        }

        /**
         * Gets the value associated to a key in the session flash data, then deletes it.
         * @param string $key Key to get value.
         * @return mixed Returns the value if exists or null if there is none.
         */
        public function getFlash(string $key){
            self::$flash = $this->get('FLASH_DATA') ?? [];
            if(isset(self::$flash[$key])){
                $value = self::$flash[$key];
                unset(self::$flash[$key]);
                $this->set('FLASH_DATA', self::$flash);
                return $value;
            }else{
                return null;
            }
        }

    }

?>