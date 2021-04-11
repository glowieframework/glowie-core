<?php
    namespace Glowie\Core;

    /**
     * Glowie application bootstrapper.
     * @category Bootstrapper
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 0.3-alpha
     */
    class Application{
        
        /**
         * Bootstrap Glowie application.
         */
        public function run(){
            // Store application start time
            define('GLOWIE_START_TIME', microtime(true));

            // Store application folder and base URL
            define('GLOWIE_APP_FOLDER', trim(substr($_SERVER['PHP_SELF'], 0, strpos($_SERVER['PHP_SELF'], '/app/public/index.php')), '/'));
            define('GLOWIE_BASE_URL', (!empty($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/' . GLOWIE_APP_FOLDER . '/');     
            
            // Include configuration file
            if (!file_exists('../config/Config.php')) {
                die('<strong>Configuration file not found!</strong><br>
                Please rename "app/config/Config.example.php" to "app/config/Config.php".');
            }else{
                require_once('../config/Config.php');
            }
            
            // Workaround for servers who dont support SetEnv/GetEnv
            if(getenv('GLOWIE_ENVIRONMENT') !== false){
                define('GLOWIE_ENVIRONMENT', getenv('GLOWIE_ENVIRONMENT'));
            }else{
                define('GLOWIE_ENVIRONMENT', 'development');
            }
            
            // Setup configuration environment
            if (!empty($glowieConfig[GLOWIE_ENVIRONMENT])) {
                $GLOBALS['glowieConfig'] = $glowieConfig[GLOWIE_ENVIRONMENT];
            }else{
                die('<strong>Invalid configuration environment!</strong><br>
                Please check your application settings and "app/public/.htaccess".');
            }

            // Timezone configuration
            date_default_timezone_set($GLOBALS['glowieConfig']['timezone']);

            // Start error handling
            Error::init();
            
            // Store application routing configuration
            $GLOBALS['glowieRoutes']['routes'] = [];
            $GLOBALS['glowieRoutes']['auto_routing'] = false;

            // Include application routes
            require_once('../config/Routes.php');

            // Include languages
            $GLOBALS['glowieLang']['languages'] = [];
            $GLOBALS['glowieLang']['active'] = 'en';
            foreach ($this->rglob('../languages/*.php') as $filename) require_once($filename);

            // Inlude models
            foreach ($this->rglob('../models/*.php') as $filename) require_once($filename);
            
            // Include controllers
            foreach ($this->rglob('../controllers/*.php') as $filename) require_once($filename);
            
            // Include view helpers
            require_once('../views/helpers/Helpers.php');

            // Initialize router
            Rails::init();
        }

        /**
         * Find pathnames from a directory matching a pattern recursively.
         * @param string $pattern Valid pathname pattern.
         * @return string[] Array with pathnames.
         */
        private function rglob(string $pattern){
            $files = glob($pattern);
            foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
                $files = array_merge($files, $this->rglob($dir . '/' . basename($pattern)));
            }
            return $files;
        }

    }

?>