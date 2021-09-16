<?php
    namespace Glowie\Core\CLI;

    use Glowie\Core\Database\Kraken;
    use Glowie\Core\Exception\ConsoleException;
    use Glowie\Core\Error\HandlerCLI;
    use Glowie\Core\Config;
    use Glowie\Core\Buffer;
    use Util;

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
         * Current command.
         * @var string
         */
        private static $command = '';

        /**
         * Command arguments.
         * @var array
         */
        private static $args = [];

        /**
         * Stores if Firefly is running through CLI.
         * @var bool
         */
        private static $isCLI = true;

        /**
         * Firefly templates folder.
         * @var string
         */
        private static $templateFolder = __DIR__ . '/Templates/';

        /**
         * Firefly current working folder path.
         * @var string
         */
        private static $appFolder = '';

        /**
         * Runs the command line tool and bootstraps Glowie modules.
         */
        public static function run(){
            // Register settings
            global $argv;
            self::$command = '';
            self::$args = $argv;
            self::$isCLI = true;
            self::$appFolder = 'app/';

            // Store application start time
            define('APP_START_TIME', microtime(true));

            // Store application folder and base URL
            define('APP_FOLDER', trim(getcwd(), '/'));
            define('APP_BASE_URL', APP_FOLDER . '/');

            // Load configuration
            if (!file_exists(self::$appFolder . 'config/Config.php')) {
                self::print('<bg="red"><color="black">Configuration file not found!</color></bg>');
                self::print('<color="red">Please copy "app/config/Config.example.php" to "app/config/Config.php"</color>');
                die();
            }

            // Loads the configuration file
            Config::load(self::$appFolder);

            // Register error handling
            HandlerCLI::register();

            // Timezone configuration
            date_default_timezone_set(Config::get('timezone', 'America/Sao_Paulo'));

            // Include application routes
            require(self::$appFolder . 'config/Routes.php');

            // Include languages
            foreach (Util::getFiles(self::$appFolder . 'languages/*.php') as $filename) include($filename);

            // Gets the command
            array_shift(self::$args);
            if(!isset(self::$args[0])){
                self::print('<bg="magenta"><color="black">Welcome to Firefly!</color></bg>');
                self::print('<color="yellow">To view a list of valid commands, use php firefly help</color>');
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
            self::$command = '';
            self::$args = $args;
            self::$isCLI = false;
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
            self::$command = $command;
            switch ($command) {
                case 'shine':
                    self::shine();
                    break;
                case 'sandbox':
                    self::sandbox();
                    break;
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
                case 'create-command':
                    self::createCommand();
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
                    $classname = 'Glowie\Commands\\' . Util::pascalCase($command);
                    if(class_exists($classname)){
                        $class = new $classname;
                        if (!is_callable([$class, 'run'])) throw new ConsoleException($command, self::$args, "Command \"{$classname}\" does not have a run() method");
                        if (is_callable([$class, 'init'])) $class->init();
                        $class->run();
                    }else{
                        throw new ConsoleException($command, self::$args, 'Unknown command ' . $command);
                    }
                    break;
            }
        }

        /**
         * Prints a text in the console.
         * @param string $text Text to print.
         * @param bool $break (Optional) Break line at the end.
         */
        public static function print(string $text, bool $break = true){
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
         * Asks for the user input in the console.
         * @param string $message (Optional) Message to prompt to the user.
         * @param string $default (Optional) Default value to return if no input is provided.
         * @return string Returns the input value as a string.
         */
        public static function input(string $message = '', string $default = ''){
            if(!self::$isCLI) return $default;
            self::print($message, false);
            $value = trim(fgets(STDIN));
            if($value === '') return $default;
            return $value;
        }

        /**
         * Checks if an argument has been passed, otherwise asks for the user input in the console.
         * @param string $arg Argument name to check. If passed, its value will be returned.
         * @param string $message (Optional) Message to prompt to the user if the argument was not passed.
         * @param string $default (Optional) Default value to return if the argument is not passed or no input is provided.
         * @return string Returns the value as a string.
         */
        public static function argOrInput(string $arg, string $message = '', string $default = ''){
            return self::$args[$arg] ?? self::input($message, $default);
        }

        /**
         * Gets an argument value.
         * @param string $arg Argument key to get.
         * @param mixed $default (Optional) Default value to return if the key does not exist.
         * @return mixed Returns the value if exists or the default if not.
         */
        public static function getArg(string $arg, $default = null){
            return self::$args[$arg] ?? $default;
        }

        /**
         * Gets all arguments as an associative array.
         * @return array Returns an array of arguments.
         */
        public static function getArgs(){
            return self::$args;
        }

        /**
         * Returns the application working folder.
         * @return string App folder as string.
         */
        public static function getAppFolder(){
            return self::$appFolder;
        }

        /**
         * Starts the local development server.
         */
        private static function shine(){
            // Checks if CLI is running
            if(!self::$isCLI) throw new ConsoleException(self::$command, self::$args, 'This command cannot be used from outside the console');

            // Checks if host was filled
            $host = self::getArg('host', 'localhost');

            // Checks if port was filled
            $port = self::getArg('port', 8080);

            // Starts the server
            self::print('<color="green">Starting local development server...</color>');
            self::print('<color="yellow">To shutdown the server press Ctrl+C</color>');
            system('php -S ' . $host . ':' . $port .' -t app/public ' . __DIR__ . '/Server.php');
        }

        /**
         * Starts the REPL interactive mode.
         */
        private static function sandbox(){
            // Checks if CLI is running
            if(!self::$isCLI) throw new ConsoleException(self::$command, self::$args, 'This command cannot be used from outside the console');

            // Starts the interactive mode
            self::print('<color="green">Welcome to Firefly Sandbox!</color>');
            self::print('<color="yellow">Press Ctrl+C to quit the interactive mode</color>');

            // REPL
            while (true) {
                // Starting tag
                self::print('<color="cyan">sandbox >> </color>', false);

                // Gets the current command
                $_command = trim(fgets(STDIN));
                if($_command == 'quit' || $_command == 'exit') break;

                // Captures the output buffer
                Buffer::start();

                // Evaluates the command
                eval($_command . ';');

                // Flushes the buffer
                self::print('<color="yellow">>> ' . Buffer::get() . '</color>');
            }
        }

        /**
         * Deletes all files in **app/storage/cache** folder.
         */
        private static function clearCache(){
            if(!is_writable(self::$appFolder . 'storage/cache')) throw new ConsoleException(self::$command, self::$args, 'Directory "app/storage/cache" is not writable, please check your chmod settings');
            self::print("<color=\"blue\">Clearing cache...</color>");
            foreach (Util::getFiles(self::$appFolder . 'storage/cache/*.tmp') as $filename) unlink($filename);
            self::print('<color="green">Cache cleared successfully!</color>');
            return true;
        }

        /**
         * Deletes all files in **app/storage/session** folder.
         */
        private static function clearSession(){
            if(!is_writable(self::$appFolder . 'storage/session')) throw new ConsoleException(self::$command, self::$args, 'Directory "app/storage/session" is not writable, please check your chmod settings');
            self::print("<color=\"blue\">Clearing session data...</color>");
            foreach (Util::getFiles(self::$appFolder . 'storage/session/*') as $filename) unlink($filename);
            self::print('<color="green">Session data cleared successfully!</color>');
            return true;
        }

        /**
         * Clears the error log.
         */
        private static function clearLog(){
            if(!is_writable(self::$appFolder . 'storage')) throw new ConsoleException(self::$command, self::$args, 'Directory "app/storage" is not writable, please check your chmod settings');
            self::print("<color=\"blue\">Clearing error log...</color>");
            file_put_contents(self::$appFolder . 'storage/error.log', '');
            self::print('<color="green">Error log cleared successfully!</color>');
            return true;
        }

        /**
         * Tests the database connection for the current environment.
         */
        private static function testDatabase(){
            // Attempts to create the connection
            self::print('<color="blue">Connecting to the database...</color>');
            $time = microtime(true);
            new Kraken();

            // Prints the result
            $time = round((microtime(true) - $time) * 1000, 2) . 'ms';
            self::print('<color="green">Database connected successfully in ' . $time . '!</color>');
            return true;
        }

        /**
         * Creates a new command.
         */
        private static function createCommand(){
            // Checks permissions
            if(!is_writable(self::$appFolder . 'commands')) throw new ConsoleException(self::$command, self::$args, 'Directory "app/commands" is not writable, please check your chmod settings');

            // Checks if name was filled
            $name = self::argOrInput('name', 'Command name: ');

            // Validates the controller name
            if(empty($name)) throw new ConsoleException(self::$command, self::$args, 'Missing required argument "name" for this command');

            // Creates the file
            $name = Util::pascalCase($name);
            $template = file_get_contents(self::$templateFolder . 'Command.php');
            $template = str_replace('__FIREFLY_TEMPLATE_NAME__', $name, $template);
            file_put_contents(self::$appFolder . 'commands/' . $name . '.php', $template);

            // Success message
            self::print("<color=\"green\">Command {$name} created successfully!</color>");
            return true;
        }

        /**
         * Creates a new controller.
         */
        private static function createController(){
            // Checks permissions
            if(!is_writable(self::$appFolder . 'controllers')) throw new ConsoleException(self::$command, self::$args, 'Directory "app/controllers" is not writable, please check your chmod settings');

            // Checks if name was filled
            $name = self::argOrInput('name', 'Controller name: ');

            // Validates the controller name
            if(empty($name)) throw new ConsoleException(self::$command, self::$args, 'Missing required argument "name" for this command');

            // Creates the file
            $name = Util::pascalCase($name);
            $template = file_get_contents(self::$templateFolder . 'Controller.php');
            $template = str_replace('__FIREFLY_TEMPLATE_NAME__', $name, $template);
            file_put_contents(self::$appFolder . 'controllers/' . $name . '.php', $template);

            // Success message
            self::print("<color=\"green\">Controller {$name} created successfully!</color>");
            return true;
        }

        /**
         * Creates a new language file.
         */
        private static function createLanguage(){
            // Checks permissions
            if(!is_writable(self::$appFolder . 'languages')) throw new ConsoleException(self::$command, self::$args, 'Directory "app/languages" is not writable, please check your chmod settings');

            // Checks if id was filled
            $id = self::argOrInput('id', 'Language id: ');

            // Validates the language id
            if(empty($id)) throw new ConsoleException(self::$command, self::$args, 'Missing required argument "id" for this command');

            // Creates the file
            $id = trim(strtolower($id));
            $template = file_get_contents(self::$templateFolder . 'Language.php');
            $template = str_replace('__FIREFLY_TEMPLATE_NAME__', $id, $template);
            file_put_contents(self::$appFolder . 'languages/' . $id . '.php', $template);

            // Success message
            self::print("<color=\"green\">Language file {$id} created successfully!</color>");
            return true;
        }

        /**
         * Creates a new middleware.
         */
        private static function createMiddleware(){
            // Checks permissions
            if(!is_writable(self::$appFolder . 'middlewares')) throw new ConsoleException(self::$command, self::$args, 'Directory "app/middlewares" is not writable, please check your chmod settings');

            // Checks if name was filled
            $name = self::argOrInput('name', 'Middleware name: ');

            // Validates the middleware name
            if(empty($name)) throw new ConsoleException(self::$command, self::$args, 'Missing required argument "name" for this command');

            // Creates the file
            $name = Util::pascalCase($name);
            $template = file_get_contents(self::$templateFolder . 'Middleware.php');
            $template = str_replace('__FIREFLY_TEMPLATE_NAME__', $name, $template);
            file_put_contents(self::$appFolder . 'middlewares/' . $name . '.php', $template);

            // Success message
            self::print("<color=\"green\">Middleware {$name} created successfully!</color>");
            return true;
        }

        /**
         * Creates a new migration.
         */
        private static function createMigration(){
            // Checks permissions
            if(!is_writable(self::$appFolder . 'migrations')) throw new ConsoleException(self::$command, self::$args, 'Directory "app/migrations" is not writable, please check your chmod settings');

            // Checks if name was filled
            $name = self::argOrInput('name', 'Migration name: ');

            // Validates the migration name
            if(empty($name)) throw new ConsoleException(self::$command, self::$args, 'Missing required argument "name" for this command');

            // Creates the file
            $cleanName = Util::pascalCase($name);
            $name = 'm' . date('Y_m_d_His_') . $cleanName;
            $template = file_get_contents(self::$templateFolder . 'Migration.php');
            $template = str_replace('__FIREFLY_TEMPLATE_NAME__', $name, $template);
            file_put_contents(self::$appFolder . 'migrations/' . $name . '.php', $template);

            // Success message
            self::print("<color=\"green\">Migration {$cleanName} created successfully!</color>");
            return true;
        }

        /**
         * Creates a new model.
         */
        private static function createModel(){
            // Checks permissions
            if(!is_writable(self::$appFolder . 'models')) throw new ConsoleException(self::$command, self::$args, 'Directory "app/models" is not writable, please check your chmod settings');

            // Checks if name was filled
            $name = self::argOrInput('name', 'Model name: ');

            // Validates the model name
            if(empty($name)) throw new ConsoleException(self::$command, self::$args, 'Missing required argument "name" for this command');

            // Checks if table was filled
            $default_table = Util::snakeCase($name);
            $table = self::argOrInput('table', "Model table ({$default_table}): ", $default_table);
            $table = trim($table);

            // Checks if primary key was filled
            $primary = self::argOrInput('primary', 'Primary key name (id): ', 'id');
            $primary = trim($primary);

            // Checks if timestamps was filled
            $timestamps = self::argOrInput('timestamps', 'Handle timestamp fields (true): ', 'true');
            $timestamps = filter_var($timestamps, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';

            // Checks if created field was filled
            $created_at = self::argOrInput('created', 'Created at field name (created_at): ', 'created_at');
            $created_at = trim($created_at);

            // Checks if updated field was filled
            $updated_at = self::argOrInput('created', 'Created at field name (updated_at): ', 'updated_at');
            $updated_at = trim($updated_at);

            // Creates the file
            $name = Util::pascalCase($name);
            $template = file_get_contents(self::$templateFolder . 'Model.php');
            $template = str_replace(['__FIREFLY_TEMPLATE_NAME__', '__FIREFLY_TEMPLATE_TABLE__', '__FIREFLY_TEMPLATE_PRIMARY__', '__FIREFLY_TEMPLATE_TIMESTAMPS__', '__FIREFLY_TEMPLATE_CREATED__', '__FIREFLY_TEMPLATE_UPDATED__'], [$name, $table, $primary, $timestamps, $created_at, $updated_at], $template);
            file_put_contents(self::$appFolder . 'models/' . $name . '.php', $template);

            // Success message
            self::print("<color=\"green\">Model {$name} created successfully!</color>");
            return true;
        }

        /**
         * Runs pending migrations.
         */
        private static function migrate(){
            // Checks if steps were filled
            $steps = self::getArg('steps', 'all');

            // Stores current state
            $migrateRun = false;
            $stepsDone = 0;

            // Loops through all the migration files
            foreach (glob(self::$appFolder . 'migrations/*.php') as $filename){
                // Checks current state
                if($steps != 'all' && $stepsDone == (int)$steps) break;

                // Stores the execution start time
                $time = microtime(true);

                // Gets the migration class name
                $name = pathinfo($filename, PATHINFO_FILENAME);
                $classname = 'Glowie\Migrations\\' . $name;
                if(!class_exists($classname)) continue;

                // Instantiates the migration class
                $migration = new $classname;
                if (is_callable([$migration, 'init'])) $migration->init();

                // Checks if the migration was already applied
                if(!$migration->isApplied()){
                    self::print("<color=\"blue\">Applying migration {$name}...</color>");
                    $migration->run();
                    $migration->saveMigration();
                    $migrateRun = true;
                    $stepsDone++;
                    $time = round((microtime(true) - $time) * 1000, 2) . 'ms';
                    self::print("<color=\"green\">Migration {$name} applied successfully in {$time}!</color>");
                }
            }

            // Checks if no migrations were run
            if(!$migrateRun){
                self::print('<color="yellow">There are no new migrations to apply.</color>');
                return true;
            }else{
                self::print('<color="yellow">All new migrations were applied successfully.</color>');
                return true;
            }
        }

        /**
         * Rolls back applied migrations.
         */
        private static function rollback(){
            // Checks if steps were filled
            $steps = self::getArg('steps', 1);

            // Stores current state
            $rollbackRun = false;
            $stepsDone = 0;

            // Loops through all the migration files
            foreach (array_reverse(glob(self::$appFolder . 'migrations/*.php')) as $filename) {
                // Checks current state
                if($steps != 'all' && $stepsDone == (int)$steps) break;

                // Stores the execution start time
                $time = microtime(true);

                // Gets the migration class name
                $name = pathinfo($filename, PATHINFO_FILENAME);
                $classname = 'Glowie\Migrations\\' . $name;
                if(!class_exists($classname)) continue;

                // Instantiates the migration class
                $migration = new $classname;
                if (is_callable([$migration, 'init'])) $migration->init();

                // Checks if the migration was already applied
                if($migration->isApplied()){
                    self::print("<color=\"blue\">Rolling back migration {$name}...</color>");
                    $migration->rollback();
                    $migration->deleteMigration();
                    $rollbackRun = true;
                    $stepsDone++;
                    $time = round((microtime(true) - $time) * 1000, 2) . 'ms';
                    self::print("<color=\"green\">Migration {$name} rolled back successfully in {$time}!</color>");
                }
            }

            // Checks if migrations were rolled back
            if(!$rollbackRun){
                self::print('<color="yellow">There are no migrations to rollback.</color>');
                return true;
            }else{
                self::print('<color="yellow">Migrations were rolled back successfully.</color>');
                return true;
            }
        }

        /**
         * Prints the current Glowie and PHP CLI versions.
         */
        private static function version(){
            self::print('<color="magenta">Firefly | Glowie ' . Util::getVersion() . '</color>');
            self::print('<color="blue">Running in PHP CLI ' . phpversion() . '</color>');
            return 'Firefly | Glowie ' . Util::getVersion();
        }

        /**
         * Prints the help message.
         */
        private static function help(){
            self::print('<color="magenta">Firefly commands:</color>');
            self::print('');
            self::print('  <color="yellow">shine</color> <color="blue">--host --port</color> | Starts the local development server');
            self::print('  <color="yellow">sandbox</color> | Starts the REPL interactive mode');
            self::print('  <color="yellow">clear-cache</color> | Clears the application cache folder');
            self::print('  <color="yellow">clear-session</color> | Clears the application session folder');
            self::print('  <color="yellow">clear-log</color> | Clears the application error log');
            self::print('  <color="yellow">test-database</color> | Tests the database connection for the current environment');
            self::print('  <color="yellow">create-command</color> <color="blue">--name</color> | Creates a new command for your application');
            self::print('  <color="yellow">create-controller</color> <color="blue">--name</color> | Creates a new controller for your application');
            self::print('  <color="yellow">create-language</color> <color="blue">--id</color> | Creates a new language file for your application');
            self::print('  <color="yellow">create-middleware</color> <color="blue">--name</color> | Creates a new middleware for your application');
            self::print('  <color="yellow">create-migration</color> <color="blue">--name</color> | Creates a new migration for your application');
            self::print('  <color="yellow">create-model</color> <color="blue">--name --table --primary --timestamps --created --updated</color> | Creates a new model for your application');
            self::print('  <color="yellow">migrate</color> <color="blue">--steps</color> | Applies pending migrations from your application');
            self::print('  <color="yellow">rollback</color> <color="blue">--steps</color> | Rolls back the last applied migration');
            self::print('  <color="yellow">version</color> | Displays current Firefly version');
            self::print('  <color="yellow">help</color> | Displays this help message');
        }

    }

?>