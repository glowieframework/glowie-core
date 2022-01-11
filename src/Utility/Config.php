<?php

    /**
     * Glowie configuration handler.
     * @category Configuration
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.1
     */
    class Config{

        /**
         * Current config settings.
         * @var array
         */
        private static $config = [];

        /**
         * Loads the configuration file.
         */
        public static function load(){
            $file = Util::location('config/Config.php');
            if(!self::hasLoaded()){
                if (!file_exists($file)) {
                    die('<strong>Configuration file was not found!</strong><br>
                    Please copy "app/config/Config.example.php" to "app/config/Config.php".');
                } else {
                    self::$config = require_once($file);
                }
            }
        }

        /**
         * Gets a configuration variable.
         * @param string $key Key to get value (accepts dot notation keys).
         * @param mixed $default (Optional) Default value to return if the key does not exist.
         * @return mixed Returns the value if exists or the default if not.
         */
        public static function get(string $key, $default = null){
            return Util::arrayGet(self::$config, $key, $default);
        }

        /**
         * Sets a runtime configuration variable.
         * @param string $key Key to set value (accepts dot notation keys).
         * @param mixed $value Value to set.
         */
        public static function set(string $key, $value){
            return Util::arraySet(self::$config, $key, $value);
        }

        /**
         * Checks if a configuration exists.
         * @param string $key Key to check.
         * @return bool Returns true or false.
         */
        public static function has(string $key){
            return !is_null(self::get($key));
        }

        /**
         * Checks if the configuration file was already loaded.
         * @return bool Returns true if yes, false otherwise.
         */
        public static function hasLoaded(){
            return !empty(self::$config);
        }

    }

?>