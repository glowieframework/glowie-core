<?php
    namespace Glowie\Core\Tools;

    /**
     * Singleton helper for Glowie application.
     * @category Container
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) Glowie
     * @license MIT
     * @link https://eugabrielsilva.tk/glowie
     */
    class Singleton{

        /**
         * Current created instances.
         * @var array
         */
        private static $instances = [];

        /**
         * Gets/creates a singleton instance of a class.
         * @param string|object $class Classname or an object instance.
         * @return object Returns the singleton instance.
         */
        public static function of($class){
            $class = is_object($class) ? get_class($class) : $class;
            if(!isset(self::$instances[$class])) self::$instances[$class] = new $class;
            return self::$instances[$class];
        }

        /**
         * Deletes an existing singleton instance of a class, if any.
         * @param string|object $class Classname or an object instance.
         */
        public static function delete($class){
            $class = is_object($class) ? get_class($class) : $class;
            if(isset(self::$instances[$class])) unset(self::$instances[$class]);
        }

    }

?>