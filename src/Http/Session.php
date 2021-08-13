<?php
    namespace Glowie\Core\Http;

    use Glowie\Core\Exception\FileException;

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
            self::$flash = $this->get('app_flash_data') ?? [];
        }

        /**
         * Registers the session save path and INI settings.
         */
        public static function register(){
            // Save path
            $sessdir = '../storage/session';
            if(!is_writable($sessdir)) throw new FileException('Session: Directory "app/storage/session" is not writable, please check your chmod settings');
            session_save_path($sessdir);

            // INI settings
            ini_set('session.name', 'app_session');
            ini_set('session.gc_probability', '1');
            ini_set('session.gc_divisor', '50');
            ini_set('session.gc_maxlifetime', (string)(GLOWIE_CONFIG['session_lifetime'] ?? 120));
            ini_set('session.cookie_httponly', '1');
            ini_set('session.use_cookies', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.use_strict_mode', '1');
        }

        /**
         * Gets the value associated to a key in the session data.
         * @param string $key Key to get value.
         * @return mixed Returns the value if exists or null if there is none.
         */
        public function __get(string $key){
            return $this->get($key);
        }

        /**
         * Gets the value associated to a key in the session data.
         * @param string $key Key to get value.
         * @param mixed $default (Optional) Default value to return if the key does not exists.
         * @return mixed Returns the value if exists or the default if not.
         */
        public function get(string $key, $default = null){
            if (!isset($_SESSION)) session_start();
            return $_SESSION[$key] ?? $default;
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
         * Gets the session data as JSON.
         * @param int $flags (Optional) JSON encoding flags (same as in `json_encode()` function).
         * @param int $depth (Optional) JSON encoding maximum depth (same as in `json_encode()` function).
         * @return string The resulting JSON string.
         */
        public function toJson(int $flags = 0, int $depth = 512){
            if(!isset($_SESSION)) session_start();
            return json_encode($_SESSION, $flags, $depth);
        }

        /**
         * Sets the value for a key in the session flash data.
         * @param string $key Key to set value.
         * @param mixed $value Value to set.
         */
        public function setFlash(string $key, $data){
            self::$flash = $this->get('app_flash_data') ?? [];
            self::$flash[$key] = $data;
            $this->set('app_flash_data', self::$flash);
        }

        /**
         * Gets the value associated to a key in the session flash data, then deletes it.
         * @param string $key Key to get value.
         * @param mixed $default (Optional) Default value to return if the key does not exists.
         * @return mixed Returns the value if exists or the default if not.
         */
        public function getFlash(string $key, $default = null){
            self::$flash = $this->get('app_flash_data') ?? [];
            if(isset(self::$flash[$key])){
                $value = self::$flash[$key];
                unset(self::$flash[$key]);
                $this->set('app_flash_data', self::$flash);
                return $value;
            }else{
                return $default;
            }
        }

    }

?>