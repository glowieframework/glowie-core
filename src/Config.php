<?php
    namespace Glowie\Core;
    
    /**
     * Glowie configuration handler.
     * @category Configuration
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.0
     */
    class Config{

        /**
         * Current config settings.
         * @var array
         */
        private static $config = [];
        
        /**
         * Loads the configuration file.
         * @param string $appFolder (Optional) Application "app" folder path relative to the running script.
         */
        public static function load(string $appFolder = '../'){
            if(!self::hasLoaded()){
                if (!file_exists($appFolder . 'config/Config.php')) {
                    die('<strong>Configuration file was not found!</strong><br>
                    Please copy "app/config/Config.example.php" to "app/config/Config.php".');
                } else {
                    self::$config = require_once($appFolder . 'config/Config.php');
                }
            }
        }

        /**
         * Gets a configuration variable.
         * @param string $key Key to get value.
         * @param mixed $default (Optional) Default value to return if the key does not exist.
         * @return mixed Returns the value if exists or the default if not.
         */
        public static function get(string $key, $default = null){
            return self::$config[$key] ?? $default;
        }

        /**
         * Checks if a configuration exists.
         * @param string $key Key to check.
         * @return bool Returns true or false.
         */
        public static function has(string $key){
            return isset(self::$config[$key]);
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