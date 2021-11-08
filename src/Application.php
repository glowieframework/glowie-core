<?php
    namespace Glowie\Core;

    use Babel;
    use Glowie\Core\Http\Session;
    use Glowie\Core\Error\Handler;
    use Glowie\Core\Http\Rails;

    /**
     * Glowie application bootstrapper.
     * @category Bootstrapper
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.0
     */
    class Application{

        /**
         * Bootstrap Glowie application.
         */
        public static function run(){
            // Store application start time
            define('APP_START_TIME', microtime(true));

            // Store application folder and base URL
            define('APP_FOLDER', trim(substr($_SERVER['PHP_SELF'], 0, strpos($_SERVER['PHP_SELF'], '/app/public/index.php')), '/'));
            define('APP_BASE_URL', (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/' . APP_FOLDER . (!empty(APP_FOLDER) ? '/' : ''));

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

            // Initialize router
            Rails::init();

            // Flush the output buffer if no errors were thrown
            Buffer::flush();
        }

    }

?>