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
        private static $config;
        
        /**
         * Loads the configuration file.
         * @param string $appFolder (Optional) Application "app" folder path relative to the running script.
         */
        public static function load(string $appFolder = '../'){
            if (!file_exists($appFolder . 'config/Config.php')) {
                die('<strong>Configuration file not found!</strong><br>
                Please rename "app/config/Config.example.php" to "app/config/Config.php".');
            }else{
                self::$config = require_once($appFolder . 'config/Config.php');
                if(!defined('GLOWIE_CONFIG')) define('GLOWIE_CONFIG', true);
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

    }

?>