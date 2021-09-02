<?php
    namespace Glowie\Core;

    /**
     * Output buffer handler for Glowie application.
     * @category Output buffer
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.0
     */
    class Buffer{

        /**
         * Starts the output buffer.
         * @return bool Returns true on success or false on failure.
         */
        public static function start(){
            return ob_start();
        }

        /**
         * Sends the output buffer to the browser and ends the output buffering.
         * @return bool Returns true on success or false on failure.
         */
        public static function flush(){
            return ob_end_flush();
        }

        /**
         * Ends the current output buffer.
         * @return bool Returns true on success or false on failure.
         */
        public static function clean(){
            return ob_end_clean();
        }

        /**
         * Gets the current output buffer content as a string and ends it.
         * @return string|bool Returns the content or false on fail.
         */
        public static function get(){
            return ob_get_clean();
        }

    }

?>