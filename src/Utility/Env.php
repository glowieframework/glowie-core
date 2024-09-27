<?php

/**
 * Glowie environment configuration handler.
 * @category Configuration
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://gabrielsilva.dev.br/glowie
 * @see https://gabrielsilva.dev.br/glowie/docs/latest/getting-started/app-configuration
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
        $file = Util::location('../.env');
        if (!is_file($file)) return;
        $file = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($file as $config) {
            if (Util::startsWith($config, '#')) continue;
            $config = explode('=', $config, 2);
            if (count($config) != 2) continue;
            if (self::get($config[0]) !== null) continue;
            self::set($config[0], $config[1]);
        }
    }

    /**
     * Gets an environment configuration variable.
     * @param string $key Key to get value.
     * @param mixed $default (Optional) Default value to return if the key does not exist.
     * @return mixed Returns the value if exists or the default if not.
     */
    public static function get(string $key, $default = null)
    {
        // Gets the value from the default environment
        $value = getenv(trim($key), true);
        if ($value !== false) return $value;

        // Gets the value from Apache environment
        if (function_exists('apache_getenv')) {
            $value = apache_getenv(trim($key), true);
            if ($value !== false) return $value;
        }

        // Fallback to other environments
        return $_ENV[$key] ?? self::$env[$key] ?? $default;
    }

    /**
     * Sets a runtime environment configuration variable.
     * @param string $key Key to set value.
     * @param string $value Value to set.
     */
    public static function set(string $key, string $value)
    {
        putenv(sprintf('%s=%s', trim($key), trim($value)));
        if (function_exists('apache_setenv')) apache_setenv(trim($key), trim($value), true);
        $_ENV[$key] = $value;
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
