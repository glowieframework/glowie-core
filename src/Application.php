<?php
    namespace Glowie;

    /**
     * Glowie application bootstrapper.
     * @category Bootstrapper
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 0.2-alpha
     */
    class Application{

         /**
         * Error handler.
         * @var Error
         */
        private $handler;
        
        /**
         * Bootstrap Glowie application.
         */
        public function run(){       
            // Store application starting time
            $GLOBALS['glowieTimer'] = microtime(true);
            
            // Check minimum PHP version
            if (version_compare(phpversion(), '7.4.9', '<')) {
                die('<strong>Unsupported PHP version!</strong><br>
                Glowie requires PHP version 7.4.9 or higher, you are using ' . phpversion() . '.');
            }
            
            // Check configuration file
            if (!file_exists('../config/Config.php')) {
                die('<strong>Configuration file not found!</strong><br>
                Please rename "app/config/Config.example.php" to "app/config/Config.php".');
            }
            
            // Include configuration file
            require_once('../config/Config.php');
            
            // Check configuration environment
            if (empty($glowieConfig[getenv('GLOWIE_ENV')])) {
                die('<strong>Invalid configuration environment!</strong><br>
                Please check your application settings and .htaccess.');
            }

            // Setup configuration environment
            $GLOBALS['glowieConfig'] = $glowieConfig[getenv('GLOWIE_ENV')];

            // Timezone configuration
            date_default_timezone_set($GLOBALS['glowieConfig']['timezone']);

            // Error handling
            $this->handler = new Error();
            
            // Store application routing configuration
            $GLOBALS['glowieRoutes']['routes'] = [];
            $GLOBALS['glowieRoutes']['auto_routing'] = true;

            // Include application routes
            require_once('../config/Routes.php');

            // Include languages
            $GLOBALS['glowieLang']['languages'] = [];
            $GLOBALS['glowieLang']['active'] = 'en';
            foreach (glob('../languages/*.php') as $filename) require_once($filename);

            // Inlude models
            foreach (glob('../models/*.php') as $filename) require_once($filename);

            // Include controllers
            foreach (glob('../controllers/*.php') as $filename) require_once($filename);

            // Initialize router
            $router = new \Rails();
            $router->init();
        }

    }

?>