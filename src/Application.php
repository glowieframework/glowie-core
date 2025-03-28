<?php

namespace Glowie\Core;

use Babel;
use Config;
use Env;
use Glowie\Core\Http\Session;
use Glowie\Core\Error\Handler;
use Glowie\Core\Exception\PluginException;
use Glowie\Core\View\Buffer;
use Glowie\Core\Http\Rails;

/**
 * Glowie application bootstrapper.
 * @category Bootstrapper
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 */
class Application
{

    /**
     * Shared application states.
     * @var array
     */
    private $states = [];

    /**
     * Bootstrap Glowie application.
     */
    public static function run()
    {
        // Store application start time
        define('APP_START_TIME', microtime(true));

        // Store application folder and base URL
        define('APP_FOLDER', trim(substr($_SERVER['PHP_SELF'], 0, strpos($_SERVER['PHP_SELF'], '/app/public/index.php')), '/'));
        define('APP_LOCATION', dirname(getcwd()) . '/');
        define('APP_BASE_URL', (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/' . APP_FOLDER);

        // Load environment configuration
        Env::load();

        // Load configuration file
        Config::load();

        // Register error handling
        Handler::register();

        // Load language files
        Babel::load();

        // Register session save path
        Session::register();

        // Timezone configuration
        date_default_timezone_set(Config::get('other.timezone', 'America/Sao_Paulo'));

        // Load route configuration file
        Rails::load();

        // Start output buffering
        Buffer::start();

        // Initialize plugins
        foreach (Config::get('plugins', []) as $plugin) {
            if (!class_exists($plugin)) throw new PluginException("\"{$plugin}\" was not found");
            $plugin = new $plugin;
            $plugin->register();
        }

        // Initialize router
        Rails::init();

        // Flush the output buffer if no errors were thrown
        Buffer::flush();
    }

    /**
     * Sets a shared state in the application container.
     * @param string $name State name to be set.
     * @param mixed $data Data to set.
     */
    public static function setState(string $name, $data)
    {
        self::$states[$name] = $data;
    }

    /**
     * Gets a shared state from the application container.
     * @param string $name State name to get.
     * @param mixed $default (Optional) Default value to return if not exists.
     * @return mixed Returns the state or the default value if not exists.
     */
    public static function getState(string $name, $default = null)
    {
        return self::$states[$name] ?? $default;
    }
}
