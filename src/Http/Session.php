<?php
    namespace Glowie\Core\Http;

    use Glowie\Core\Exception\FileException;
    use Config;
    use Util;

    /**
     * Session manager for Glowie application.
     * @category Session manager
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.2
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
            $sessdir = Config::get('session.path', Util::location('storage/session'));
            if(!is_writable($sessdir)) throw new FileException('Session path "' . $sessdir . '" is not writable, please check your chmod settings');
            session_save_path($sessdir);

            // INI settings
            ini_set('session.name', 'app_session');
            ini_set('session.gc_probability', '1');
            ini_set('session.gc_divisor', (string)Config::get('session.gc_cleaning', 50));
            ini_set('session.gc_maxlifetime', (string)Config::get('session.lifetime', 120));
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
         * @param string $key Key to get value (accepts dot notation keys).
         * @param mixed $default (Optional) Default value to return if the key does not exist.
         * @return mixed Returns the value if exists or the default if not.
         */
        public function get(string $key, $default = null){
            if (!isset($_SESSION)) session_start();
            return Util::arrayGet($_SESSION, $key, $default);
        }

        /**
         * Sets the value for a key in the session data.
         * @param string $key Key to set value.
         * @param mixed $value Value to set.
         */
        public function __set(string $key, $value){
            $this->set($key, $value, true);
        }

        /**
         * Sets the value for a key in the session data.
         * @param string $key Key to set value (accepts dot notation keys).
         * @param mixed $value Value to set.
         * @param bool $ignoreDot (Optional) Ignore dot notation keys.
         * @return Session Current Session instance for nested calls.
         */
        public function set(string $key, $value, bool $ignoreDot = false){
            if(!isset($_SESSION)) session_start();
            if($ignoreDot){
                $_SESSION[$key] = $value;
            }else{
                Util::arraySet($_SESSION, $key, $value);
            }
            return $this;
        }

        /**
         * Removes the associated key value from the session data.
         * @param string $key Key to delete value.
         */
        public function __unset(string $key){
            $this->remove($key);
        }

        /**
         * Removes the associated key value from the session data.
         * @param string|array $key Key to delete value. You can also use an array of keys to remove.
         * @return Session Current Session instance for nested calls.
         */
        public function remove($key){
            if(!isset($_SESSION)) session_start();
            foreach((array)$key as $item){
                if (isset($_SESSION[$item])) unset($_SESSION[$item]);
            }
            return $this;
        }

        /**
         * Checks if any value has been associated to a key in the session data.
         * @param string $key Key to check.
         * @return bool Returns true or false.
         */
        public function __isset(string $key){
            return $this->has($key);
        }

        /**
         * Checks if any value has been associated to a key in the session data.
         * @param string $key Key to check (accepts dot notation keys).
         * @return bool Returns true or false.
         */
        public function has(string $key){
            if(!isset($_SESSION)) session_start();
            return Util::arrayGet($_SESSION, $key) !== null;
        }

        /**
         * Deletes all data from the session.
         * @return Session Current Session instance for nested calls.
         */
        public function flush(){
            if(!isset($_SESSION)) session_start();
            $_SESSION = [];
            self::$flash = [];
            return $this;
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
        public function toJson(int $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK, int $depth = 512){
            if(!isset($_SESSION)) session_start();
            return json_encode($_SESSION, $flags, $depth);
        }

        /**
         * Gets the session data as JSON.
         * @return string The resulting JSON string.
         */
        public function __toString(){
            return $this->toJson();
        }

        /**
         * Session debugging information.
         */
        public function __debugInfo(){
            return $_SESSION;
        }

        /**
         * Sets the value for a key in the session flash data.
         * @param string $key Key to set value.
         * @param mixed $value Value to set.
         * @return Session Current Session instance for nested calls.
         */
        public function setFlash(string $key, $value){
            self::$flash = $this->get('app_flash_data') ?? [];
            self::$flash[$key] = $value;
            return $this->set('app_flash_data', self::$flash);
        }

        /**
         * Gets the value associated to a key in the session flash data, then deletes it.
         * @param string $key Key to get value.
         * @param mixed $default (Optional) Default value to return if the key does not exist.
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