<?php

namespace Glowie\Core\View;

/**
 * Output buffer handler for Glowie application.
 * @category Output buffer
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 */
class Buffer
{

    /**
     * Starts the output buffer.
     * @return bool Returns true on success or false on failure.
     */
    public static function start()
    {
        try {
            return ob_start();
        } catch (\Throwable $th) {
            return false;
        }
    }

    /**
     * Sends the output buffer to the browser and ends the output buffering.
     * @return bool Returns true on success or false on failure.
     */
    public static function flush()
    {
        try {
            return ob_end_flush();
        } catch (\Throwable $th) {
            return false;
        }
    }

    /**
     * Ends the current output buffer.
     * @return bool Returns true on success or false on failure.
     */
    public static function clean()
    {
        try {
            return ob_end_clean();
        } catch (\Throwable $th) {
            return false;
        }
    }

    /**
     * Gets the current output buffer content as a string and ends it.
     * @return string|bool Returns the content or false on fail.
     */
    public static function get()
    {
        try {
            return ob_get_clean();
        } catch (\Throwable $th) {
            return false;
        }
    }

    /**
     * Checks if the output buffer is active.
     * @return bool Returns true if active, false otherwise.
     */
    public static function isActive()
    {
        try {
            return ob_get_length() !== false;
        } catch (\Throwable $th) {
            return false;
        }
    }
}
