<?php
    namespace Glowie\Core;

    use Util;
    use Glowie\Core\Http\Session;
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
            define('GLOWIE_START_TIME', microtime(true));

            // Store application folder and base URL
            define('GLOWIE_APP_FOLDER', trim(substr($_SERVER['PHP_SELF'], 0, strpos($_SERVER['PHP_SELF'], '/app/public/index.php')), '/'));
            define('GLOWIE_BASE_URL', (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/' . GLOWIE_APP_FOLDER . (!empty(GLOWIE_APP_FOLDER) ? '/' : ''));

            // Load configuration file
            Config::load();

            // Register error handling
            Error::register();

            // Register session save path
            Session::register();

            // Timezone configuration
            date_default_timezone_set(Config::get('timezone', 'America/Sao_Paulo'));

            // Include application routes
            require('../config/Routes.php');

            // Include languages
            foreach (Util::getFiles('../languages/*.php') as $filename) include($filename);

            // Initialize router
            Rails::init();
        }

    }

?>