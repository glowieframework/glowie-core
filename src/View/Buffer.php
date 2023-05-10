<?php
    namespace Glowie\Core\View;

    /**
     * Output buffer handler for Glowie application.
     * @category Output buffer
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) Glowie
     * @license MIT
     * @link https://eugabrielsilva.tk/glowie
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

        /**
         * Checks if the output buffer is active.
         * @return bool Returns true if active, false otherwise.
         */
        public static function isActive(){
            return ob_get_length() !== false;
        }

    }

?>