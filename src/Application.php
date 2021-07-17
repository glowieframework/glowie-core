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

            // Include configuration file
            if (!file_exists('../config/Config.php')) {
                die('<strong>Configuration file not found!</strong><br>
                Please rename "app/config/Config.example.php" to "app/config/Config.php".');
            }else{
                require('../config/Config.php');
            }

            // Workaround for servers who dont support SetEnv/GetEnv
            if(getenv('GLOWIE_ENVIRONMENT') !== false){
                define('GLOWIE_ENVIRONMENT', getenv('GLOWIE_ENVIRONMENT'));
            }else{
                define('GLOWIE_ENVIRONMENT', 'production');
            }

            // Setup configuration environment
            if (!empty($config[GLOWIE_ENVIRONMENT])) {
                define('GLOWIE_CONFIG', $config[GLOWIE_ENVIRONMENT]);
            }else{
                die('<strong>Invalid configuration environment!</strong><br>
                Please check your application settings and "app/public/.htaccess".');
            }

            // Register error handling
            Error::register();

            // Register session save path
            Session::register();

            // Timezone configuration
            date_default_timezone_set(GLOWIE_CONFIG['timezone']);

            // Include application routes
            require('../config/Routes.php');

            // Include languages
            foreach (Util::getFiles('../languages/*.php') as $filename) include($filename);

            // Initialize router
            Rails::init();
        }

    }

?>