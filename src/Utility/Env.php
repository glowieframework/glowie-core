<?php

    /**
     * Glowie environment configuration handler.
     * @category Configuration
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.1
     */
    class Env{

        /**
         * Loads the environment configuration file.
         */
        public static function load(){
            $file = Util::location('../.env');
            if(!file_exists($file)) return;
            $file = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach($file as $config){
                if(Util::startsWith($config, '#')) continue;
                $config = explode('=', $config, 2);
                if(count($config) == 2) self::set($config[0], $config[1]);
            }
        }

        /**
         * Gets an environment configuration variable.
         * @param string $key Key to get value.
         * @param mixed $default (Optional) Default value to return if the key does not exist.
         * @return mixed Returns the value if exists or the default if not.
         */
        public static function get(string $key, $default = null){
            $value = getenv($key, true);
            if($value === false) return $default;
            return $value;
        }

        /**
         * Sets a runtime environment configuration variable.
         * @param string $key Key to set value.
         * @param mixed $value Value to set.
         */
        public static function set(string $key, $value){
            putenv(sprintf('%s=%s', $key, $value));
        }

        /**
         * Checks if an environment configuration exists.
         * @param string $key Key to check.
         * @return bool Returns true or false.
         */
        public static function has(string $key){
            return !is_null(self::get($key));
        }

    }

?>