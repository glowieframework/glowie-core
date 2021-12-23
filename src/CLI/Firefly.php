<?php
    namespace Glowie\Core\CLI;

    use Glowie\Core\Database\Kraken;
    use Glowie\Core\Exception\FileException;
    use Glowie\Core\Exception\ConsoleException;
    use Glowie\Core\Error\HandlerCLI;
    use Glowie\Core\Http\Rails;
    use Glowie\Core\View\Buffer;
    use Util;
    use Throwable;
    use Config;
    use Babel;

    /**
     * Command line tool for Glowie application.
     * @category CLI
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.1
     */
    class Firefly{

        /**
         * Console regex replacements.
         * @var array
         */
        private const REGEX = [
            '/<color="default">/i' => "\033[0m",
            '/<color="red">/i' => "\033[91m",
            '/<color="green">/i' => "\033[92m",
            '/<color="yellow">/i' => "\033[93m",
            '/<color="blue">/i' => "\033[94m",
            '/<color="magenta">/i' => "\033[95m",
            '/<color="cyan">/i' => "\033[96m",
            '/<color="gray">/i' => "\033[37m",
            '/<color="black">/i' => "\033[30m",
            '/<bg="default">/i' => "\033[49m",
            '/<bg="red">/i' => "\033[101m",
            '/<bg="green">/i' => "\033[42m",
            '/<bg="yellow">/i' => "\033[103m",
            '/<bg="blue">/i' => "\033[104m",
            '/<bg="magenta">/i' => "\033[45m",
            '/<bg="cyan">/i' => "\033[106m",
            '/<bg="gray">/i' => "\033[47m",
            '/<bg="black">/i' => "\033[40m",
            '/<\/color>/i' => "\033[0m",
            '/<\/bg>/i' => "\033[49m"
        ];

        /**
         * Firefly templates folder.
         * @var string
         */
        private const TEMPLATES_FOLDER = __DIR__ . '/Templates/';

        /**
         * Current command.
         * @var string
         */
        private static $command;

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
         * Firefly current working folder path.
         * @var string
         */
        private static $appFolder;

        /**
         * Enable silent print mode.
         * @var bool
         */
        private static $silent;

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
            self::$silent = false;

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

            // Load language files
            Babel::load(self::$appFolder);

            // Timezone configuration
            date_default_timezone_set(Config::get('other.timezone', 'America/Sao_Paulo'));

            // Load route configuration file
            Rails::load(self::$appFolder);

            // Gets the command
            array_shift(self::$args);
            if(!isset(self::$args[0])){
                self::print('<bg="magenta"><color="black">Welcome to Firefly!</color></bg>');
                self::print('<color="yellow">To see the commands list, use php firefly help</color>');
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
         * @param bool $silent (Optional) Disable any output from the command.
         */
        public static function call(string $command, array $args = [], bool $silent = false){
            // Register settings
            self::$command = '';
            self::$args = $args;
            self::$isCLI = false;
            self::$appFolder = '../';
            self::$silent = $silent;

            // Runs the command
            self::triggerCommand($command);
        }

        /**
         * Returns if the application is running through console.
         * @return bool True if application is running in CLI mode, false otherwise.
         */
        public static function isCLI(){
            return self::$isCLI;
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
            // Saves the command
            $command = strtolower($command);
            self::$command = $command;

            // Finds a valid command
            $name = Util::pascalCase($command);
            if(is_callable([self::class, '__' . $name])){
                $name = '__' . $name;
                self::$name();
            }else{
                $name = 'Glowie\Commands\\' . $name;
                if(class_exists($name)){
                    $class = new $name;
                    $class->run();
                }else{
                    throw new ConsoleException($command, self::$args, "Unknown command \"{$command}\"");
                }
            }
        }

        /**
         * Prints a text in the console.
         * @param string $text Text to print.
         * @param bool $break (Optional) Break line at the end.
         */
        public static function print(string $text, bool $break = true){
            if(self::$isCLI){
                // If running in console, replace colors, backgrounds and closing tags
                $text = preg_replace(array_keys(self::REGEX), array_values(self::REGEX), $text);
            }else{
                // If outside the console, remove closing tags
                $text = preg_replace(['/<\/color>/i', '/<\/bg>/i'], '', $text);
            }

            // Remove remaining colors or backgrounds
            $text = preg_replace(['/<color="(.+)">/i', '/<bg="(.+)">/i'], '', $text);

            // Prints the text
            if(!self::$silent) echo $text . ($break ? PHP_EOL : '');
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
         * Checks if an argument has been passed, otherwise throws an exception.
         * @param string $arg Argument name to check. If passed, its value will be returned.
         * @return string Returns the value as a string if the argument was passed.
         * @throws ConsoleException Throws an exception if the argument was not passed.
         */
        public static function argOrFail(string $arg){
            if(!isset(self::$args[$arg])) throw new ConsoleException(self::$command, self::$args, 'Missing required argument "' . $arg . '" for this command');
            return self::$args[$arg];
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
        private static function __shine(){
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
        private static function __sandbox(){
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
                $__command = trim(fgets(STDIN));
                if($__command == 'quit' || $__command == 'exit') break;

                // Captures the output buffer
                Buffer::start();

                // Evaluates the command
                $__command .= ';';
                eval($__command);

                // Flushes the buffer
                self::print('<color="yellow">>> ' . Buffer::get() . '</color>');
            }
        }

        /**
         * Deletes all files in **app/storage/cache** folder.
         */
        private static function __clearCache(){
            if(!is_writable(self::$appFolder . 'storage/cache')) throw new FileException('Directory "app/storage/cache" is not writable, please check your chmod settings');
            self::print("<color=\"blue\">Clearing cache...</color>");
            foreach (Util::getFiles(self::$appFolder . 'storage/cache/*.tmp') as $filename) unlink($filename);
            self::print('<color="green">Cache cleared successfully!</color>');
            return true;
        }

        /**
         * Deletes all files in **app/storage/session** folder.
         */
        private static function __clearSession(){
            if(!is_writable(self::$appFolder . 'storage/session')) throw new FileException('Directory "app/storage/session" is not writable, please check your chmod settings');
            self::print("<color=\"blue\">Clearing session data...</color>");
            foreach (Util::getFiles(self::$appFolder . 'storage/session/*') as $filename) unlink($filename);
            self::print('<color="green">Session data cleared successfully!</color>');
            return true;
        }

        /**
         * Clears the error log.
         */
        private static function __clearLog(){
            if(!is_writable(self::$appFolder . 'storage')) throw new FileException('Directory "app/storage" is not writable, please check your chmod settings');
            self::print("<color=\"blue\">Clearing error log...</color>");
            file_put_contents(self::$appFolder . 'storage/error.log', '');
            self::print('<color="green">Error log cleared successfully!</color>');
            return true;
        }

        /**
         * Tests a database connection.
         */
        private static function __testDatabase(){
            // Checks if name was filled
            $name = self::argOrInput('name', "Database connection (default): ", 'default');
            $name = trim($name);

            // Attempts to create the connection
            self::print('<color="blue">Connecting to "' . $name . '" database...</color>');
            $time = microtime(true);
            new Kraken('glowie', $name);

            // Prints the result
            $time = round((microtime(true) - $time) * 1000, 2) . 'ms';
            self::print('<color="green">Database "' . $name . '" connected successfully in ' . $time . '!</color>');
            return true;
        }

        /**
         * Creates a new command.
         */
        private static function __createCommand(){
            // Checks permissions
            if(!is_dir(self::$appFolder . 'commands')) mkdir(self::$appFolder . 'commands');
            if(!is_writable(self::$appFolder . 'commands')) throw new FileException('Directory "app/commands" is not writable, please check your chmod settings');

            // Checks if name was filled
            $name = self::argOrInput('name', 'Command name: ');

            // Validates the controller name
            if(empty($name)) throw new ConsoleException(self::$command, self::$args, 'Missing required argument "name" for this command');

            // Creates the file
            $name = Util::pascalCase($name);
            $template = file_get_contents(self::TEMPLATES_FOLDER . 'Command.php');
            $template = str_replace('__FIREFLY_TEMPLATE_NAME__', $name, $template);
            file_put_contents(self::$appFolder . 'commands/' . $name . '.php', $template);

            // Success message
            self::print("<color=\"green\">Command {$name} created successfully!</color>");
            return true;
        }

        /**
         * Creates a new controller.
         */
        private static function __createController(){
            // Checks permissions
            if(!is_dir(self::$appFolder . 'controllers')) mkdir(self::$appFolder . 'controllers');
            if(!is_writable(self::$appFolder . 'controllers')) throw new FileException('Directory "app/controllers" is not writable, please check your chmod settings');

            // Checks if name was filled
            $name = self::argOrInput('name', 'Controller name: ');

            // Validates the controller name
            if(empty($name)) throw new ConsoleException(self::$command, self::$args, 'Missing required argument "name" for this command');

            // Creates the file
            $name = Util::pascalCase($name);
            $template = file_get_contents(self::TEMPLATES_FOLDER . 'Controller.php');
            $template = str_replace('__FIREFLY_TEMPLATE_NAME__', $name, $template);
            file_put_contents(self::$appFolder . 'controllers/' . $name . '.php', $template);

            // Success message
            self::print("<color=\"green\">Controller {$name} created successfully!</color>");
            return true;
        }

        /**
         * Creates a new language file.
         */
        private static function __createLanguage(){
            // Checks permissions
            if(!is_dir(self::$appFolder . 'languages')) mkdir(self::$appFolder . 'languages');
            if(!is_writable(self::$appFolder . 'languages')) throw new FileException('Directory "app/languages" is not writable, please check your chmod settings');

            // Checks if name was filled
            $name = self::argOrInput('name', 'Language name: ');

            // Validates the language id
            if(empty($name)) throw new ConsoleException(self::$command, self::$args, 'Missing required argument "name" for this command');

            // Creates the file
            $name = trim(strtolower($name));
            copy(self::TEMPLATES_FOLDER . 'Language.php', self::$appFolder . 'languages/' . $name . '.php');

            // Success message
            self::print("<color=\"green\">Language file {$name} created successfully!</color>");
            return true;
        }

        /**
         * Creates a new middleware.
         */
        private static function __createMiddleware(){
            // Checks permissions
            if(!is_dir(self::$appFolder . 'middlewares')) mkdir(self::$appFolder . 'middlewares');
            if(!is_writable(self::$appFolder . 'middlewares')) throw new FileException('Directory "app/middlewares" is not writable, please check your chmod settings');

            // Checks if name was filled
            $name = self::argOrInput('name', 'Middleware name: ');

            // Validates the middleware name
            if(empty($name)) throw new ConsoleException(self::$command, self::$args, 'Missing required argument "name" for this command');

            // Creates the file
            $name = Util::pascalCase($name);
            $template = file_get_contents(self::TEMPLATES_FOLDER . 'Middleware.php');
            $template = str_replace('__FIREFLY_TEMPLATE_NAME__', $name, $template);
            file_put_contents(self::$appFolder . 'middlewares/' . $name . '.php', $template);

            // Success message
            self::print("<color=\"green\">Middleware {$name} created successfully!</color>");
            return true;
        }

        /**
         * Creates a new migration.
         */
        private static function __createMigration(){
            // Checks permissions
            if(!is_dir(self::$appFolder . 'migrations')) mkdir(self::$appFolder . 'migrations');
            if(!is_writable(self::$appFolder . 'migrations')) throw new FileException('Directory "app/migrations" is not writable, please check your chmod settings');

            // Checks if name was filled
            $name = self::argOrInput('name', 'Migration name: ');

            // Validates the migration name
            if(empty($name)) throw new ConsoleException(self::$command, self::$args, 'Missing required argument "name" for this command');

            // Creates the file
            $cleanName = Util::pascalCase($name);
            $name = 'm' . date('Y_m_d_His_') . $cleanName;
            $template = file_get_contents(self::TEMPLATES_FOLDER . 'Migration.php');
            $template = str_replace('__FIREFLY_TEMPLATE_NAME__', $name, $template);
            file_put_contents(self::$appFolder . 'migrations/' . $name . '.php', $template);

            // Success message
            self::print("<color=\"green\">Migration {$cleanName} created successfully!</color>");
            return true;
        }

        /**
         * Creates a new model.
         */
        private static function __createModel(){
            // Checks permissions
            if(!is_dir(self::$appFolder . 'models')) mkdir(self::$appFolder . 'models');
            if(!is_writable(self::$appFolder . 'models')) throw new FileException('Directory "app/models" is not writable, please check your chmod settings');

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
            $timestamps = (bool)self::argOrInput('timestamps', 'Handle timestamp fields (true): ', true);
            $timestamps = $timestamps ? 'true' : 'false';

            // Checks if created field was filled
            $created_at = self::argOrInput('created', 'Created at field name (created_at): ', 'created_at');
            $created_at = trim($created_at);

            // Checks if updated field was filled
            $updated_at = self::argOrInput('created', 'Created at field name (updated_at): ', 'updated_at');
            $updated_at = trim($updated_at);

            // Creates the file
            $name = Util::pascalCase($name);
            $template = file_get_contents(self::TEMPLATES_FOLDER . 'Model.php');
            $template = str_replace(['__FIREFLY_TEMPLATE_NAME__', '__FIREFLY_TEMPLATE_TABLE__', '__FIREFLY_TEMPLATE_PRIMARY__', '__FIREFLY_TEMPLATE_TIMESTAMPS__', '__FIREFLY_TEMPLATE_CREATED__', '__FIREFLY_TEMPLATE_UPDATED__'], [$name, $table, $primary, $timestamps, $created_at, $updated_at], $template);
            file_put_contents(self::$appFolder . 'models/' . $name . '.php', $template);

            // Success message
            self::print("<color=\"green\">Model {$name} created successfully!</color>");
            return true;
        }

        /**
         * Creates a new unit test.
         */
        private static function __createTest(){
            // Checks permissions
            if(!is_dir(self::$appFolder . 'tests')) mkdir(self::$appFolder . 'tests');
            if(!is_writable(self::$appFolder . 'tests')) throw new FileException('Directory "app/tests" is not writable, please check your chmod settings');

            // Checks if name was filled
            $name = self::argOrInput('name', 'Test name: ');

            // Validates the test name
            if(empty($name)) throw new ConsoleException(self::$command, self::$args, 'Missing required argument "name" for this command');

            // Creates the file
            $name = Util::pascalCase($name);
            $template = file_get_contents(self::TEMPLATES_FOLDER . 'Test.php');
            $template = str_replace('__FIREFLY_TEMPLATE_NAME__', $name, $template);
            file_put_contents(self::$appFolder . 'tests/' . $name . '.php', $template);

            // Success message
            self::print("<color=\"green\">Test {$name} created successfully!</color>");
            return true;
        }

        /**
         * Runs pending migrations.
         */
        private static function __migrate(){
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
                self::print('');
                self::print('<color="yellow">' . $stepsDone . ' migrations were applied successfully.</color>');
                return true;
            }
        }

        /**
         * Rolls back applied migrations.
         */
        private static function __rollback(){
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
                self::print('');
                self::print('<color="yellow">' . $stepsDone . ' migrations were rolled back successfully.</color>');
                return true;
            }
        }

        /**
         * Runs the application unit tests.
         */
        private static function __test(){
            // Checks if name was filled
            $name = self::getArg('name');

            // Validates the test name
            if(!empty($name)){
                $filename = self::$appFolder . 'tests/' . $name . '.php';
                if(!file_exists($filename)) throw new FileException('Test "' . $name . '" does not exist in "app/tests"');
                $files = [$filename];
            }else{
                $files = glob(self::$appFolder . 'tests/*.php');
            }

            // Checks for empty tests folder
            if(empty($files)){
                self::print('<color="yellow">There are no tests to run.</color>');
                return false;
            }

            // Gets the bail option
            $bail = (bool)self::getArg('bail', false);

            // Stores the result
            $result = ['success' => 0, 'fail' => 0];

            // Loops through all the test files
            foreach ($files as $file){
                // Gets the test class name
                $name = pathinfo($file, PATHINFO_FILENAME);
                $classname = 'Glowie\Tests\\' . $name;
                if(!class_exists($classname)) continue;

                // Gets the test methods
                $tests = array_filter(get_class_methods($classname), function($name){
                    return Util::startsWith($name, 'test');
                });

                // Checks if there are any tests
                if(empty($tests)) continue;

                // Prints the classname
                self::print('<color="blue">Running ' . $name . ' tests...</color>');

                // Run init method if exists
                try {
                    $time = microtime(true);
                    $testClass = new $classname;
                    if (is_callable([$testClass, 'init'])) $testClass->init();
                } catch (Throwable $e){
                    $time = round((microtime(true) - $time) * 1000, 2) . 'ms';
                    self::print("<bg=\"red\"><color=\"black\">Test initialization failed:</color></bg> <color=\"red\">{$name} in {$time}. {$e->getMessage()}</color>");

                    // Stop tests after failure
                    if($bail){
                        self::print('');
                        self::print("<color=\"yellow\">Partial tests were done: {$result['success']} successful, {$result['fail']} failed.</color>");
                        return false;
                    }
                }

                // Run each test
                foreach($tests as $test){
                    // Stores the test execution start time
                    $time = microtime(true);

                    // Runs the test
                    try {
                        $testClass->{$test}();
                        $time = round((microtime(true) - $time) * 1000, 2) . 'ms';
                        $result['success']++;
                        self::print("<color=\"green\">Test {$name}->{$test}() passed in {$time}!</color>");
                    } catch (Throwable $e) {
                        $time = round((microtime(true) - $time) * 1000, 2) . 'ms';
                        self::print("<bg=\"red\"><color=\"black\">Test failed:</color></bg> <color=\"red\">{$name}->{$test}() in {$time}. {$e->getMessage()}</color>");
                        $result['fail']++;

                        // Stop tests after failure
                        if($bail){
                            self::print('');
                            self::print("<color=\"yellow\">Partial tests were done: {$result['success']} successful, {$result['fail']} failed.</color>");
                            return false;
                        }
                    }
                };

                // Run cleanup method if exists
                try {
                    $time = microtime(true);
                    if (is_callable([$testClass, 'cleanup'])) $testClass->cleanup();
                } catch(Throwable $e) {
                    $time = round((microtime(true) - $time) * 1000, 2) . 'ms';
                    self::print("<bg=\"red\"><color=\"black\">Test cleanup failed:</color></bg> <color=\"red\">{$name} in {$time}. {$e->getMessage()}</color>");

                    // Stop tests after failure
                    if($bail){
                        self::print('');
                        self::print("<color=\"yellow\">Partial tests were done: {$result['success']} successful, {$result['fail']} failed.</color>");
                        return false;
                    }
                }
            }

            // Prints result message
            self::print('');
            self::print("<color=\"yellow\">All tests were done: {$result['success']} successful, {$result['fail']} failed.</color>");
            return true;
        }

        /**
         * Prints the current Glowie and PHP CLI versions.
         */
        private static function __version(){
            self::print('<color="magenta">Firefly | Glowie ' . Util::getVersion() . '</color>');
            self::print('<color="blue">Running in PHP CLI ' . phpversion() . '</color>');
            return 'Firefly | Glowie ' . Util::getVersion();
        }

        /**
         * Prints the help message.
         */
        private static function __help(){
            self::print('<color="magenta">Firefly commands:</color>');
            self::print('');
            self::print('  <color="yellow">shine</color> <color="blue">--host --port</color> | Starts the local development server');
            self::print('  <color="yellow">sandbox</color> | Starts the REPL interactive mode');
            self::print('  <color="yellow">clear-cache</color> | Clears the application cache folder');
            self::print('  <color="yellow">clear-session</color> | Clears the application session folder');
            self::print('  <color="yellow">clear-log</color> | Clears the application error log');
            self::print('  <color="yellow">test-database</color> <color="blue">--name</color> | Tests a database connection');
            self::print('  <color="yellow">create-command</color> <color="blue">--name</color> | Creates a new command for your application');
            self::print('  <color="yellow">create-controller</color> <color="blue">--name</color> | Creates a new controller for your application');
            self::print('  <color="yellow">create-language</color> <color="blue">--name</color> | Creates a new language file for your application');
            self::print('  <color="yellow">create-middleware</color> <color="blue">--name</color> | Creates a new middleware for your application');
            self::print('  <color="yellow">create-migration</color> <color="blue">--name</color> | Creates a new migration for your application');
            self::print('  <color="yellow">create-model</color> <color="blue">--name --table --primary --timestamps --created --updated</color> | Creates a new model for your application');
            self::print('  <color="yellow">create-test</color> <color="blue">--name</color> | Creates a new unit test for your application');
            self::print('  <color="yellow">migrate</color> <color="blue">--steps</color> | Applies pending migrations from your application');
            self::print('  <color="yellow">rollback</color> <color="blue">--steps</color> | Rolls back the last applied migration');
            self::print('  <color="yellow">test</color> <color="blue">--name --bail</color> | Runs the application unit tests');
            self::print('  <color="yellow">version</color> | Displays current Firefly version');
            self::print('  <color="yellow">help</color> | Displays this help message');
        }

    }

?>