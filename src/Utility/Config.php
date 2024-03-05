<?php

    use Glowie\Core\Exception\FileException;

    /**
     * Glowie configuration handler.
     * @category Configuration
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) Glowie
     * @license MIT
     * @link https://gabrielsilva.dev.br/glowie
     */
    class Config{

        /**
         * Current config settings.
         * @var array
         */
        private static $config = [];

        /**
         * Loads the configuration files.
         */
        public static function load(){
            // Load main configuration file
            $file = Util::location('config/Config.php');
            if(!is_file($file)) throw new FileException('Config file "' . $file . '" was not found');
            self::$config = require_once($file);

            // Load other configuration files
            foreach(glob(Util::location('config/*.php')) as $file){
                $basename = pathinfo($file, PATHINFO_BASENAME);
                if($basename == 'Config.php' || $basename == 'Routes.php') continue;
                $file = require_once($file);
                self::$config = array_merge(self::$config, $file);
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

    }

?>