<?php
    namespace Glowie\Core\CLI;

    use Glowie\Core\Database\Kraken;
    use Glowie\Core\Exception\ConsoleException;
    use Util;
    use Exception;

    /**
     * Command line tool for Glowie application.
     * @category CLI
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.0
     */
    class Firefly{

        /**
         * Console foreground color codes.
         * @var array
         */
        private const COLORS = ['default' => "\033[0m", 'red' => "\033[91m", 'green' => "\033[92m", 'yellow' => "\033[93m", 'blue' => "\033[94m", 'magenta' => "\033[95m", 'cyan' => "\033[96m", 'gray' => "\033[37m", 'black' => "\033[30m"];

        /**
         * Console background color codes.
         * @var array
         */
        private const BACKGROUNDS = ['default' => "\033[49m", 'red' => "\033[101m", 'green' => "\033[42m", 'yellow' => "\033[103m", 'blue' => "\033[104m", 'magenta' => "\033[45m", 'cyan' => "\033[106m", 'gray' => "\033[47m", 'black' => "\033[40m"];

        /**
         * Command arguments.
         * @var array
         */
        private static $args;

        /**
         * Stores if Firefly is running through CLI.
         * @var bool
         */
        private static $isCLI;

        /**
         * Firefly templates folder.
         * @var string
         */
        private static $templateFolder;

        /**
         * Firefly current working folder path.
         * @var string
         */
        private static $appFolder;

        /**
         * Runs the command line tool.
         */
        public static function run(){
            // Register settings
            global $argv;
            self::$args = $argv;
            self::$isCLI = true;
            self::$templateFolder = 'vendor/glowieframework/glowie-core/src/CLI/Templates/';
            self::$appFolder = 'app/';

            // Gets the command
            array_shift(self::$args);
            if(!isset(self::$args[0])){
                self::print('<bg="magenta"><color="black">Welcome to Firefly!</color></bg>');
                self::print('<color="blue">To view a list of valid commands, use</color> <color="yellow">php firefly help</color>.');
                return;
            }

            // Runs the command
            $command = trim(self::$args[0]);
            self::parseArgs();
            self::triggerCommand($command);
        }

        /**
         * Calls a Firefly command outside the terminal.
         * @param string $command Firefly command to call.
         * @param array $args (Optional) Associative array of arguments to pass with the command.
         */
        public static function call(string $command, array $args = []){
            // Register settings
            self::$args = $args;
            self::$isCLI = false;
            self::$templateFolder = '../../vendor/glowieframework/glowie-core/src/CLI/Templates/';
            self::$appFolder = '../';

            // Runs the command
            self::triggerCommand($command);
        }

        /**
         * Parses the CLI arguments.
         */
        private static function parseArgs(){
            // Removes the command from the args
            array_shift(self::$args);

            // Parses the arguments as an associative array
            $args = [];
            foreach(self::$args as $value){
                $match = [];
                if(preg_match('/--(.+)=(.+)/', $value, $match)) $args[strtolower($match[1])] = $match[2];
            }

            // Returns the result
            self::$args = $args;
        }

        /**
         * Triggers a Firefly command.
         * @param string $command Command to trigger.
         */
        private static function triggerCommand(string $command){
            $command = strtolower($command);
            switch ($command) {
                case 'clear-cache':
                    self::clearCache();
                    break;
                case 'clear-session':
                    self::clearSession();
                    break;
                case 'clear-log':
                    self::clearLog();
                    break;
                case 'test-database':
                    self::testDatabase();
                    break;
                case 'create-controller':
                    self::createController();
                    break;
                case 'create-language':
                    self::createLanguage();
                    break;
                case 'create-middleware':
                    self::createMiddleware();
                    break;
                case 'create-migration':
                    self::createMigration();
                    break;
                case 'create-model':
                    self::createModel();
                    break;
                case 'migrate':
                    self::migrate();
                    break;
                case 'rollback':
                    self::rollback();
                    break;
                case 'version':
                    self::version();
                    break;
                case 'help':
                    self::help();
                    break;
                default:
                    self::print('<bg="red"><color="black">Unknown command: ' . $command . '</color></bg>');
                    self::print('<color="blue">To view a list of valid commands, use</color> <color="yellow">php firefly help</color>.');
                    self::error('Unknown command: ' . $command);
                    break;
            }
        }

        /**
         * Prints a formatted text in the console.
         * @var string $text Text to print.
         * @var bool $break (Optional) Break line at the end.
         */
        private static function print(string $text, bool $break = true){
            // Checks if CLI is running
            if(!self::$isCLI) return;

            // Replace color codes
            foreach(self::COLORS as $key => $value) $text = preg_replace('/<color="' . $key . '">/', $value, $text);
            foreach(self::BACKGROUNDS as $key => $value) $text = preg_replace('/<bg="' . $key . '">/', $value, $text);

            // Replace closing brackets
            $text = preg_replace(['/<\/color>/', '/<\/bg>/'], [self::COLORS['default'], self::BACKGROUNDS['default']], $text);
            echo $text . ($break ? PHP_EOL : '');
        }

        /**
         * Triggers an error in the script.
         * @var string $message Error message to send.
         */
        private static function error(string $message){
            // Checks if CLI is running
            if(self::$isCLI) return;

            // Throw error
            throw new ConsoleException('Firefly: ' . $message);
        }

        /**
         * Deletes all files in **app/storage/cache** folder.
         */
        private static function clearCache(){
            if(!is_writable(self::$appFolder . 'storage/cache')){
                self::print('<bg="red"><color="black">Oops, something went wrong!</color></bg>');
                self::print('<color="red">Directory "app/storage/cache" is not writable, please check your chmod settings</color>');
                self::error('Directory "app/storage/cache" is not writable, please check your chmod settings');
                return;
            }
            foreach (Util::getFiles(self::$appFolder . 'storage/cache/*.tmp') as $filename) unlink($filename);
            self::print('<color="green">Cache cleared successfully!</color>');
            return true;
        }

        /**
         * Deletes all files in **app/storage/session** folder.
         */
        private static function clearSession(){
             if(!is_writable(self::$appFolder . 'storage/session')){
                self::print('<bg="red"><color="black">Oops, something went wrong!</color></bg>');
                self::print('<color="red">Directory "app/storage/session" is not writable, please check your chmod settings</color>');
                self::error('Directory "app/storage/session" is not writable, please check your chmod settings');
                return;
            }
            foreach (Util::getFiles(self::$appFolder . 'storage/session/*') as $filename) unlink($filename);
            self::print('<color="green">Session data cleared successfully!</color>');
            return true;
        }

        /**
         * Clears the error log.
         */
        private static function clearLog(){
            if(!is_writable(self::$appFolder . 'storage')){
                self::print('<bg="red"><color="black">Oops, something went wrong!</color></bg>');
                self::print('<color="red">Directory "app/storage" is not writable, please check your chmod settings</color>');
                self::error('Directory "app/storage" is not writable, please check your chmod settings');
                return;
            }
            file_put_contents(self::$appFolder . 'storage/error.log', '');
            self::print('<color="green">Error log cleared successfully!</color>');
            return true;
        }

        /**
         * Tests an environment database connection.
         */
        private static function testDatabase(){
            // Checks if CLI is running
            if(self::$isCLI){
                // Checks configuration file
                if (!file_exists(self::$appFolder . 'config/Config.php')) {
                    self::print('<bg="red"><color="black">Configuration file not found!</color></bg>');
                    self::print('Please rename <color="yellow">"app/config/Config.example.php"</color> to <color="green">"app/config/Config.php"</color>.');
                    return false;
                }

                // Loads the configuration file
                require(self::$appFolder . 'config/Config.php');

                // Checks if environment was filled
                if (isset(self::$args['env'])) {
                    $env = trim(self::$args['env']);
                } else {
                    self::print("Configuration environment to use database (production): ", false);
                    $env = trim(fgets(STDIN));
                }
                if (empty($env)) $env = 'production';

                // Loads the environment setting
                if (empty($config[$env])) {
                    self::print('<bg="red"><color="black">Invalid configuration environment!</color></bg>');
                    self::print('<color="red">Please check your application settings.</color>');
                    return false;
                }

                // Sets the environment setting
                if (!defined('GLOWIE_CONFIG')) define('GLOWIE_CONFIG', $config[$env]);

                // Sets error reporting
                error_reporting(E_ALL);
                ini_set('display_errors', '1');
                ini_set('display_startup_errors', '1');
                mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            }

            // Attempts to create the connection
            self::print('<color="blue">Connecting to the database...</color>');
            $time = microtime(true);
            try {
                new Kraken();
            } catch (Exception $e) {
                self::print('<bg="red"><color="black">Database connection failed!</color></bg>');
                self::print('<color="red">' . $e->getMessage() . '</color>');
                self::error('Database connection failed! ' . $e->getMessage());
                return false;
            }

            // Prints the result
            $time = round((microtime(true) - $time), 5);
            self::print('<color="green">Database connected successfully in ' . $time . ' seconds!</color>');
            return true;
        }

        /**
         * Creates a new controller.
         */
        private static function createController(){
            // Checks permissions
            if(!is_writable(self::$appFolder . 'controllers')){
                self::print('<bg="red"><color="black">Oops, something went wrong!</color></bg>');
                self::print('<color="red">Directory "app/controllers" is not writable, please check your chmod settings</color>');
                self::error('Directory "app/controllers" is not writable, please check your chmod settings');
                return false;
            }

            // Checks if name was filled
            if(isset(self::$args['name'])){
                $name = trim(self::$args['name']);
            }else if(self::$isCLI){
                self::print("Controller name: ", false);
                $name = trim(fgets(STDIN));
            }

            // Validates the controller name
            if(empty($name)){
                self::print('<color="red">Controller name cannot be empty!</color>');
                self::error('Missing required argument "name" for this command');
                return false;
            }

            // Creates the file
            $name = Util::camelCase($name, true);
            try {
                $template = file_get_contents(self::$templateFolder . 'Controller.php');
                $template = str_replace('__FIREFLY_TEMPLATE_NAME__', $name, $template);
                file_put_contents(self::$appFolder . 'controllers/' . $name . '.php', $template);
            } catch (Exception $e) {
                self::print('<bg="red"><color="black">Controller creation failed!</color></bg>');
                self::print('<color="red">' . $e->getMessage() . '</color>');
                self::error('Controller creation failed! ' . $e->getMessage());
                return false;
            }

            // Success message
            self::print("<color=\"green\">Controller {$name} created successfully!</color>");
            return true;
        }

        /**
         * Creates a new language file.
         */
        private static function createLanguage(){
            // Checks permissions
            if(!is_writable(self::$appFolder . 'languages')){
                self::print('<bg="red"><color="black">Oops, something went wrong!</color></bg>');
                self::print('<color="red">Directory "app/languages" is not writable, please check your chmod settings</color>');
                self::error('Directory "app/languages" is not writable, please check your chmod settings');
                return false;
            }

            // Checks if id was filled
            if(isset(self::$args['id'])){
                $id = trim(self::$args['id']);
            }else if(self::$isCLI){
                self::print("Language id: ", false);
                $id = trim(fgets(STDIN));
            }

            // Validates the language id
            if(empty($id)){
                self::print('<color="red">Language id cannot be empty!</color>');
                self::error('Missing required argument "id" for this command');
                return false;
            }

            // Creates the file
            try {
                $template = file_get_contents(self::$templateFolder . 'Language.php');
                $template = str_replace('__FIREFLY_TEMPLATE_NAME__', $id, $template);
                file_put_contents(self::$appFolder . 'languages/' . $id . '.php', $template);
            } catch (Exception $e) {
                self::print('<bg="red"><color="black">Language file creation failed!</color></bg>');
                self::print('<color="red">' . $e->getMessage() . '</color>');
                self::error('Language file creation failed! ' . $e->getMessage());
                return false;
            }

            // Success message
            self::print("<color=\"green\">Language file {$id} created successfully!</color>");
            return true;
        }

        /**
         * Creates a new middleware.
         */
        private static function createMiddleware(){
            // Checks permissions
            if(!is_writable(self::$appFolder . 'middlewares')){
                self::print('<bg="red"><color="black">Oops, something went wrong!</color></bg>');
                self::print('<color="red">Directory "app/middlewares" is not writable, please check your chmod settings</color>');
                self::error('Directory "app/middlewares" is not writable, please check your chmod settings');
                return false;
            }

            // Checks if name was filled
            if(isset(self::$args['name'])){
                $name = trim(self::$args['name']);
            }else if(self::$isCLI){
                self::print("Middleware name: ", false);
                $name = trim(fgets(STDIN));
            }

            // Validates the middleware name
            if(empty($name)){
                self::print('<color="red">Middleware name cannot be empty!</color>');
                self::error('Missing required argument "name" for this command');
                return false;
            }

            // Creates the file
            $name = Util::camelCase($name, true);
            try {
               $template = file_get_contents(self::$templateFolder . 'Middleware.php');
                $template = str_replace('__FIREFLY_TEMPLATE_NAME__', $name, $template);
                file_put_contents(self::$appFolder . 'middlewares/' . $name . '.php', $template);
            } catch (Exception $e) {
                self::print('<bg="red"><color="black">Middleware creation failed!</color></bg>');
                self::print('<color="red">' . $e->getMessage() . '</color>');
                self::error('Middleware creation failed! ' . $e->getMessage());
                return false;
            }

            // Success message
            self::print("<color=\"green\">Middleware {$name} created successfully!</color>");
            return true;
        }

        /**
         * Creates a new migration.
         */
        private static function createMigration(){
            // Checks permissions
            if(!is_writable(self::$appFolder . 'migrations')){
                self::print('<bg="red"><color="black">Oops, something went wrong!</color></bg>');
                self::print('<color="red">Directory "app/migrations" is not writable, please check your chmod settings</color>');
                self::error('Directory "app/migrations" is not writable, please check your chmod settings');
                return;
            }

            // Checks if name was filled
            if(isset(self::$args['name'])){
                $name = trim(self::$args['name']);
            }else if(self::$isCLI){
                self::print("Migration name: ", false);
                $name = trim(fgets(STDIN));
            }

            // Validates the migration name
            if(empty($name)){
                self::print('<color="red">Migration name cannot be empty!</color>');
                self::error('Missing required argument "name" for this command');
                return false;
            }

            // Creates the file
            $cleanName = Util::camelCase($name, true);
            $name = 'm' . date('Y_m_d_His_') . $cleanName;
            try {
               $template = file_get_contents(self::$templateFolder . 'Migration.php');
                $template = str_replace('__FIREFLY_TEMPLATE_NAME__', $name, $template);
                file_put_contents(self::$appFolder . 'migrations/' . $name . '.php', $template);
            } catch (Exception $e) {
                self::print('<bg="red"><color="black">Migration creation failed!</color></bg>');
                self::print('<color="red">' . $e->getMessage() . '</color>');
                self::error('Migration creation failed! ' . $e->getMessage());
                return false;
            }

            // Success message
            self::print("<color=\"green\">Migration {$cleanName} created successfully!</color>");
            return true;
        }

        /**
         * Creates a new model.
         */
        private static function createModel(){
            // Checks permissions
            if(!is_writable(self::$appFolder . 'models')){
                self::print('<bg="red"><color="black">Oops, something went wrong!</color></bg>');
                self::print('<color="red">Directory "app/models" is not writable, please check your chmod settings</color>');
                self::error('Directory "app/models" is not writable, please check your chmod settings');
                return false;
            }

            // Checks if name was filled
            if(isset(self::$args['name'])){
                $name = trim(self::$args['name']);
            }else if(self::$isCLI){
                self::print("Model name: ", false);
                $name = trim(fgets(STDIN));
            }

            // Validates the model name
            if(empty($name)){
                self::print('<color="red">Model name cannot be empty!</color>');
                self::error('Missing required argument "name" for this command');
                return false;
            }

            // Checks if table was filled
            $default_table = strtolower(Util::camelCase($name));
            if(isset(self::$args['table'])){
                $table = trim(self::$args['table']);
            }else if(self::$isCLI){
                self::print("Model table ({$default_table}): ", false);
                $table = trim(fgets(STDIN));
            }
            if(empty($table)) $table = $default_table;

            // Checks if primary key was filled
            if(isset(self::$args['primary'])){
                $primary = trim(self::$args['primary']);
            }else if(self::$isCLI){
                self::print("Primary key name (id): ", false);
                $primary = trim(fgets(STDIN));
            }
            if(empty($primary)) $primary = 'id';

            // Checks if timestamps was filled
            if(isset(self::$args['timestamps'])){
                $timestamps = self::$args['timestamps'];
            }else if(self::$isCLI){
                self::print("Handle timestamp fields (yes): ", false);
                $timestamps = strtolower(trim(fgets(STDIN)));
            }

            // Parses timestamps value
            if ((self::$isCLI && empty($timestamps)) || $timestamps === 'true' || $timestamps === true) {
                $timestamps = 'true';
            } else {
                $timestamps = 'false';
            }

            // Checks if created field was filled
            if(isset(self::$args['created'])){
                $created_at = self::$args['created'];
            }else if(self::$isCLI){
                self::print("Created at field name (created_at): ", false);
                $created_at = trim(fgets(STDIN));
            }
            if(empty($created_at)) $created_at = 'created_at';

            // Checks if updated field was filled
            if(isset(self::$args['updated'])){
                $updated_at = self::$args['updated'];
            }else if(self::$isCLI){
                self::print("Updated at field name (updated_at): ", false);
                $updated_at = trim(fgets(STDIN));
            }
            if(empty($updated_at)) $updated_at = 'updated_at';

            // Creates the file
            $name = Util::camelCase($name, true);
            try {
                $template = file_get_contents(self::$templateFolder . 'Model.php');
                $template = str_replace(['__FIREFLY_TEMPLATE_NAME__', '__FIREFLY_TEMPLATE_TABLE__', '__FIREFLY_TEMPLATE_PRIMARY__', '__FIREFLY_TEMPLATE_TIMESTAMPS__', '__FIREFLY_TEMPLATE_CREATED__', '__FIREFLY_TEMPLATE_UPDATED__'], [$name, $table, $primary, $timestamps, $created_at, $updated_at], $template);
                file_put_contents(self::$appFolder . 'models/' . $name . '.php', $template);
            } catch (Exception $e) {
                self::print('<bg="red"><color="black">Model creation failed!</color></bg>');
                self::print('<color="red">' . $e->getMessage() . '</color>');
                self::error('Model creation failed! ' . $e->getMessage());
                return false;
            }

            // Success message
            self::print("<color=\"green\">Model {$name} created successfully!</color>");
            return true;
        }

        /**
         * Runs pending migrations.
         */
        private static function migrate(){
            // Loads the config and tests database
            $result = self::testDatabase();
            if($result === false) return;

            // Checks if steps were filled
            if(isset(self::$args['steps'])){
                $steps = trim(self::$args['steps']);
            }

            // Stores current state
            $migrateRun = false;
            $stepsDone = 0;

            // Validates the steps
            if(empty($steps)) $steps = 'all';

            // Loops through all the migration files
            foreach (glob(self::$appFolder . 'migrations/*.php') as $filename){
                // Checks current state
                if($steps != 'all' && $stepsDone == $steps) break;

                // Stores the execution start time
                $time = microtime(true);

                // Gets the migration class name
                $name = pathinfo($filename, PATHINFO_FILENAME);
                $classname = 'Glowie\Migrations\\' . $name;

                // Instantiates the migration class
                $migration = new $classname;
                if (is_callable([$migration, 'init'])) $migration->init();

                try {
                    // Checks if the migration was already applied
                    if(!$migration->isApplied()){
                        $migration->run();
                        $migration->saveMigration();
                        $migrateRun = true;
                        $stepsDone++;
                        $time = round((microtime(true) - $time), 5);
                        self::print("<color=\"green\">Migration {$name} applied successfully in {$time} seconds!</color>");
                    }
                } catch (Exception $e) {
                    self::print("<bg=\"red\"><color=\"black\">Failed to apply migration {$name}!</color></bg>");
                    self::print('<color="red">' . $e->getMessage() .'</color>');
                    self::error("Failed to apply migration {$name}! " . $e->getMessage());
                    return false;
                }
            }

            // Checks if no migrations were run
            if(!$migrateRun){
                self::print('<color="yellow">There are no new migrations to apply.</color>');
                return true;
            }else{
                self::print('<color="green">All new migrations were applied successfully.</color>');
                return true;
            }
        }

        /**
         * Rolls back applied migrations.
         */
        private static function rollback(){
            // Loads the config and tests database
            $result = self::testDatabase();
            if($result === false) return;

            // Checks if steps were filled
            if(isset(self::$args['steps'])){
                $steps = trim(self::$args['steps']);
            }

            // Stores current state
            $rollbackRun = false;
            $stepsDone = 0;

            // Validates the steps
            if(empty($steps)) $steps = 1;

            // Loops through all the migration files
            foreach (array_reverse(glob(self::$appFolder . 'migrations/*.php')) as $filename) {
                // Checks current state
                if($steps != 'all' && $stepsDone == $steps) break;

                // Stores the execution start time
                $time = microtime(true);

                // Gets the migration class name
                $name = pathinfo($filename, PATHINFO_FILENAME);
                $classname = 'Glowie\Migrations\\' . $name;

                // Instantiates the migration class
                $migration = new $classname;
                if (is_callable([$migration, 'init'])) $migration->init();

                try {
                    // Checks if the migration was already applied
                    if($migration->isApplied()){
                        $migration->rollback();
                        $migration->deleteMigration();
                        $rollbackRun = true;
                        $stepsDone++;
                        $time = round((microtime(true) - $time), 5);
                        self::print("<color=\"green\">Migration {$name} rolled back successfully in {$time} seconds!</color>");
                    }
                } catch (Exception $e) {
                    self::print("<bg=\"red\"><color=\"black\">Failed to rollback migration {$name}!</color></bg>");
                    self::print('<color="red">' . $e->getMessage() .'</color>');
                    self::error("Failed to rollback migration {$name}! " . $e->getMessage());
                    return false;
                }
            }

            // Checks if migrations were rolled back
            if(!$rollbackRun){
                self::print('<color="yellow">There are no migrations to rollback.</color>');
                return true;
            }else{
                self::print('<color="green">Migrations were rolled back successfully.</color>');
                return true;
            }
        }

        /**
         * Prints the current Firefly, Glowie and PHP CLI versions.
         */
        private static function version(){
            self::print('<bg="magenta"><color="black">Firefly by Glowie</color></bg>');
            self::print('<color="magenta">Firefly 1.0 with Glowie ' . Util::getVersion() . '</color>');
            self::print('<color="blue">Running with PHP CLI ' . phpversion() . '</color>');
            return 'Firefly 1.0 with Glowie ' . Util::getVersion();
        }

        /**
         * Prints the help message.
         */
        private static function help(){
            self::print('<color="magenta">Firefly commands:</color>');
            self::print('');
            self::print('  <color="yellow">clear-cache</color> | Clears the application cache folder');
            self::print('  <color="yellow">clear-session</color> | Clears the application session folder');
            self::print('  <color="yellow">clear-log</color> | Clears the application error log');
            self::print('  <color="yellow">test-database</color> <color="blue">--env</color> | Tests the database connection for a configuration environment');
            self::print('  <color="yellow">create-controller</color> <color="blue">--name</color> | Creates a new controller for your application');
            self::print('  <color="yellow">create-language</color> <color="blue">--id</color> | Creates a new language file for your application');
            self::print('  <color="yellow">create-middleware</color> <color="blue">--name</color> | Creates a new middleware for your application');
            self::print('  <color="yellow">create-migration</color> <color="blue">--name</color> | Creates a new migration for your application');
            self::print('  <color="yellow">create-model</color> <color="blue">--name --table --primary --timestamps --created --updated</color> | Creates a new model for your application');
            self::print('  <color="yellow">migrate</color> <color="blue">--env --steps</color> | Applies pending migrations from your application');
            self::print('  <color="yellow">rollback</color> <color="blue">--env --steps</color> | Rolls back the last applied migration');
            self::print('  <color="yellow">version</color> | Displays current Firefly version');
            self::print('  <color="yellow">help</color> | Displays this help message');
        }

    }

?>