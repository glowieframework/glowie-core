<?php
    namespace Glowie\Core;

    use Util;

    /**
     * Command line tool for Glowie application.
     * @category Command line
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.0
     */
    class Firefly{

        /**
         * Console colors.
         * @var array
         */
        private const COLORS = ['default' => "\033[0m", 'red' => "\033[91m", 'green' => "\033[92m", 'yellow' => "\033[93m", 'blue' => "\033[94m", 'magenta' => "\033[95m", 'cyan' => "\033[96m", 'gray' => "\033[37m"];

        /**
         * Firefly templates folder.
         * @var string
         */
        private const TEMPLATE_FOLDER = 'vendor/glowieframework/glowie-core/firefly/';

        /**
         * Command line arguments.
         * @var array
         */
        private static $args;

        /**
         * Runs the command line tool.
         */
        public static function run(){
            // Register arguments
            global $argv;
            self::$args = $argv;
            
            // Gets the command
            array_shift(self::$args);
            if(!isset(self::$args[0])){
                self::print('<color="magenta">Welcome to Firefly!</color>');
                self::printLine('<color="blue">To see a list of valid commands, use</color> <color="yellow">php firefly help</color>.');
                return;
            }

            // Runs the command
            $command = strtolower(trim(self::$args[0]));
            switch($command){
                case 'clear-cache':
                case '-clear-cache':
                    self::clearCache();
                    break;
                case 'clear-log':
                case '-clear-log':
                    self::clearLog();
                    break;
                case 'test-database':
                case 'test-db':
                case '-test-database':
                case '-test-db':
                    self::testDatabase();
                    break;
                case 'create-controller':
                case 'create-ct':
                case '-create-controller':
                case '-create-ct':
                    self::createController();
                    break;
                case 'create-middleware':
                case 'create-mw':
                case '-create-middleware':
                case '-create-mw':
                    self::createMiddleware();
                    break;
                case 'create-model':
                case 'create-md':
                case '-create-model':
                case '-create-md':
                    self::createModel();
                    break;
                case 'version':
                case '-version':
                case '-v':
                    self::version();
                    break;
                case 'help':
                case '-help':
                case '-h':
                case 'commands':
                case '-commands':
                    self::help();
                    break;
                default:
                    self::print('<color="red">Unknown command \''. $command . '\'!</color>');
                    self::printLine('<color="blue">To see a list of valid commands, use</color> <color="yellow">php firefly help</color>.');
                    break;
            }
        }

        /**
         * Prints a formatted text in the console.
         * @var string $text Text to print.
         */
        private static function print(string $text){
            foreach(self::COLORS as $key => $value) $text = preg_replace('/<color="' . $key . '">/', $value, $text);
            $text = preg_replace('/<\/color>/', self::COLORS['default'], $text);
            echo $text;
        }

        /**
         * Prints a formatted text in the console with a preceding line break.
         * @var string $text Text to print.
         */
        private static function printLine(string $text){
            echo "\n";
            self::print($text);
        }

        /**
         * Deletes all files in **app/storage/cache** folder.
         */
        private static function clearCache(){
            foreach (Util::getFiles('app/storage/cache/*.tmp') as $filename) unlink($filename);
            self::print('<color="green">Cache cleared successfully!</color>');
        }

        /**
         * Clears the error log.
         */
        private static function clearLog(){
            if(!is_writable('app/storage')){
                self::print('<color="red">Directory "app/storage" is not writable, please check your chmod settings</color>');
                return;
            }
            file_put_contents('app/storage/error.log', '');
            self::print('<color="green">Error log cleared successfully!</color>');
        }

        /**
         * Tests the database connection.
         */
        private static function testDatabase(){
            // Checks configuration file
            if (!file_exists('app/config/Config.php')) {
                self::print('<color="red">Configuration file not found!</color>');
                self::printLine('<color="yellow">Please rename "app/config/Config.example.php" to "app/config/Config.php".</color>');
                return;
            }

            // Loads the configuration file
            require_once('app/config/Config.php');

            // Checks if environment was filled
            if(isset(self::$args[1])){
                $env = trim(self::$args[1]);
            }else{
                self::print("Configuration environment to test (production): ");
                $env = trim(fgets(STDIN));
                if(empty($env)) $env = 'production';
            }

            // Loads the environment setting
            if(empty($config[$env])){
                self::print('<color="red">Invalid configuration environment!</color>');
                self::printLine('<color="yellow">Please check your application settings.</color>');
                return;
            }
            
            // Sets the environment setting
            define('GLOWIE_CONFIG', $config[$env]);

            // Sets error reporting
            error_reporting(E_ALL);
            ini_set('display_errors', 'On');
            ini_set('display_startup_errors', 'On');
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

            // Attempts to create the connection
            self::print('<color="blue">Testing database connection...</color>');
            $time = microtime(true);
            try {
                new Kraken();
            } catch (\Exception $e) {
                self::printLine('<color="red">Database connection failed!</color>');
                self::printLine('<color="yellow">' . $e->getMessage() . '</color>');
                return;
            }

            // Prints the result
            $time = round((microtime(true) - $time), 5);
            self::printLine('<color="green">Database connected successfully in ' . $time . ' seconds!</color>');
        }

        /**
         * Creates a new controller.
         */
        private static function createController(){           
            // Checks permissions
            if(!is_writable('app/controllers')){
                self::print('<color="red">Directory "app/controllers" is not writable, please check your chmod settings</color>');
                return;
            }

            // Checks if name was filled
            if(isset(self::$args[1])){
                $name = trim(self::$args[1]);
            }else{
                self::print("Controller name: ");
                $name = trim(fgets(STDIN));
            }

            // Validates the controller name
            if(empty($name)){
                self::print('<color="red">Controller name cannot be empty!</color>');
                return;
            }

            // Creates the file
            $name = Util::camelCase($name, true);
            $template = file_get_contents(self::TEMPLATE_FOLDER . 'Controller.php');
            $template = str_replace('__FIREFLY_TEMPLATE_NAME__', $name, $template);
            file_put_contents('app/controllers/' . $name . '.php', $template);

            // Success message
            self::print("<color=\"green\">Controller {$name} created successfully!</color>");
        }

        /**
         * Creates a new middleware.
         */
        private static function createMiddleware(){
            // Checks permissions
            if(!is_writable('app/middlewares')){
                self::print('<color="red">Directory "app/middlewares" is not writable, please check your chmod settings</color>');
                return;
            }

            // Checks if name was filled
            if(isset(self::$args[1])){
                $name = trim(self::$args[1]);
            }else{
                self::print("Middleware name: ");
                $name = trim(fgets(STDIN));
            }

            // Validates the middleware name
            if(empty($name)){
                self::print('<color="red">Middleware name cannot be empty!</color>');
                return;
            }

            // Creates the file
            $name = Util::camelCase($name, true);
            $template = file_get_contents(self::TEMPLATE_FOLDER . 'Middleware.php');
            $template = str_replace('__FIREFLY_TEMPLATE_NAME__', $name, $template);
            file_put_contents('app/middlewares/' . $name . '.php', $template);

            // Success message
            self::print("<color=\"green\">Middleware {$name} created successfully!</color>");
        }

        /**
         * Creates a new model.
         */
        private static function createModel(){
            // Checks permissions
            if(!is_writable('app/models')){
                self::print('<color="red">Directory "app/models" is not writable, please check your chmod settings</color>');
                return;
            }

            // Checks if name was filled
            if(isset(self::$args[1])){
                $name = trim(self::$args[1]);
            }else{
                self::print("Model name: ");
                $name = trim(fgets(STDIN));
            }

            // Validates the model name
            if(empty($name)){
                self::print('<color="red">Model name cannot be empty!</color>');
                return;
            }
            
            // Asks for table name
            $default_table = strtolower(Util::camelCase($name));
            self::print("Model table ({$default_table}): ");
            $table = trim(fgets(STDIN));
            if(empty($table)) $table = $default_table;

            // Asks for primary key field
            self::print("Primary key name (id): ");
            $primary = trim(fgets(STDIN));
            if(empty($primary)) $primary = 'id';

            // Asks for timestamps
            self::print("Handle timestamp fields (yes): ");
            $timestamps = strtolower(trim(fgets(STDIN)));
            if(empty($timestamps) || $timestamps == 'yes' || $timestamps == 'y' || $timestamps == 'true'){
                $timestamps = 'true';
            }else{
                $timestamps = 'false';
            }

            // Asks for created_at field
            self::print("Created at field name (created_at): ");
            $created_at = trim(fgets(STDIN));
            if(empty($created_at)) $created_at = 'created_at';

            // Asks for updated_at field
            self::print("Updated at field name (updated_at): ");
            $updated_at = trim(fgets(STDIN));
            if(empty($updated_at)) $updated_at = 'updated_at';

            // Creates the file
            $name = Util::camelCase($name, true);
            $template = file_get_contents(self::TEMPLATE_FOLDER . 'Model.php');
            $template = str_replace('__FIREFLY_TEMPLATE_NAME__', $name, $template);
            $template = str_replace('__FIREFLY_TEMPLATE_TABLE__', $table, $template);
            $template = str_replace('__FIREFLY_TEMPLATE_PRIMARY__', $primary, $template);
            $template = str_replace('__FIREFLY_TEMPLATE_TIMESTAMPS__', $timestamps, $template);
            $template = str_replace('__FIREFLY_TEMPLATE_CREATED__', $created_at, $template);
            $template = str_replace('__FIREFLY_TEMPLATE_UPDATED__', $updated_at, $template);
            file_put_contents('app/models/' . $name . '.php', $template);

            // Success message
            self::print("<color=\"green\">Model {$name} created successfully!</color>");
        }

        /**
         * Prints the current Firefly version.
         */
        private static function version(){
            self::print('<color="magenta">Firefly v1.0 by Glowie</color>');
        }

        /**
         * Prints the help message.
         */
        private static function help(){
            self::print('<color="magenta">Firefly commands:</color>');
            self::printLine('');
            self::printLine('  <color="yellow">clear-cache</color> - Clears the application cache folder');
            self::printLine('  <color="yellow">clear-log</color> - Clears the application error log');
            self::printLine('  <color="yellow">test-database</color> <color="blue"><environment></color> - Tests the database connection for a configuration environment');
            self::printLine('  <color="yellow">create-controller</color> <color="blue"><name></color> - Creates a new controller for your application');
            self::printLine('  <color="yellow">create-middleware</color> <color="blue"><name></color> - Creates a new middleware for your application');
            self::printLine('  <color="yellow">create-model</color> <color="blue"><name></color> - Creates a new model for your application');
            self::printLine('  <color="yellow">version</color> - Displays current Firefly version');
            self::printLine('  <color="yellow">help</color> - Displays this help message');
        }

    }

?>