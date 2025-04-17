<?php

/**
 * Glowie environment configuration handler.
 * @category Configuration
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 * @see https://glowie.gabrielsilva.dev.br/docs/latest/getting-started/app-configuration
 */
class Env
{

    /**
     * Current env settings.
     * @var array
     */
    private static $env = [];

    /**
     * Loads the environment configuration file.
     */
    public static function load()
    {
        // Load the file
        $file = Util::location('../.env');
        if (!is_file($file)) return;
        $file = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        // Loop through each line
        foreach ($file as $config) {
            // Ignore comments
            if (Util::startsWith($config, '#')) continue;

            // Get key and value
            $config = explode('=', $config, 2);
            if (count($config) !== 2) continue;

            // Parse key and value
            $key = trim($config[0]);
            $value = self::sanitizeValue($config[1]);

            // Ignore existing keys
            if (self::get($key) !== null) continue;

            // Sets the value
            self::set($key, $value);
        }
    }

    /**
     * Sanitizes a value and convert the type.
     * @param string $value Value to be sanitized.
     * @return mixed Returns the sanitized value.
     */
    private static function sanitizeValue(string $value)
    {
        // Trims trailing spaces
        $value = trim($value);

        // Checks for boolean or null values
        if (strtolower($value) === 'false') return false;
        if (strtolower($value) === 'true') return true;
        if (strtolower($value) === 'null') return null;

        // Strips enquoted values
        if ((Util::startsWith($value, '"') && Util::endsWith($value, '"')) || (Util::startsWith($value, "'") && Util::endsWith($value, "'"))) {
            $value = stripcslashes(substr($value, 1, -1));
        }

        // Returns the value as string
        return $value;
    }

    /**
     * Gets an environment configuration variable.
     * @param string $key Key to get value.
     * @param mixed $default (Optional) Default value to return if the key does not exist.
     * @return mixed Returns the value if exists or the default if not.
     */
    public static function get(string $key, $default = null)
    {
        // Gets the value from file context
        if (isset(self::$env[$key])) return self::$env[$key];

        // Gets the value from superglobals context
        if (isset($_ENV[$key])) return $_ENV[$key];
        if (isset($_SERVER[$key])) return $_SERVER[$key];

        // Gets the value from the getenv() context
        $value = getenv(trim($key), true);
        if ($value !== false) return $value;

        // Gets the value from Apache context
        if (function_exists('apache_getenv')) {
            $value = apache_getenv(trim($key), true);
            if ($value !== false) return $value;
        }

        // Return default value
        return $default;
    }

    /**
     * Sets a runtime environment configuration variable.
     * @param string $key Key to set value.
     * @param string $value Value to set.
     */
    public static function set(string $key, string $value)
    {
        self::$env[$key] = $value;
    }

    /**
     * Checks if an environment configuration exists.
     * @param string $key Key to check.
     * @return bool Returns true or false.
     */
    public static function has(string $key)
    {
        return !is_null(self::get($key));
    }
}
