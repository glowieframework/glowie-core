<?php
    namespace Glowie\Core\CLI;

    use Glowie\Core\Database\Kraken;
    use Glowie\Core\Exception\FileException;
    use Glowie\Core\Exception\ConsoleException;
    use Glowie\Core\Exception\PluginException;
    use Glowie\Core\Error\HandlerCLI;
    use Glowie\Core\View\Buffer;
    use Util;
    use Throwable;
    use Config;
    use Env;
    use Babel;
    use Exception;

    /**
     * Command line tool for Glowie application.
     * @category CLI
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) Glowie
     * @license MIT
     * @link https://glowie.tk
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
         * Custom commands.
         * @var array
         */
        private static $custom = [];

        /**
         * Current command.
         * @var string
         */
        private static $command = '';

        /**
         * Command arguments.
         * @var array
         */
        private static $args;

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
            self::$args = $argv;
            self::$silent = false;

            // Store application start time
            define('APP_START_TIME', microtime(true));

            // Store application folder and base URL
            define('APP_FOLDER', '');
            define('APP_BASE_URL', '');
            define('APP_LOCATION', getcwd() . '/app/');

            // Load environment configuration
            Env::load();

            // Loads the configuration file
            Config::load();

            // Register error handling
            HandlerCLI::register();

            // Load language files
            Babel::load();

            // Timezone configuration
            date_default_timezone_set(Config::get('other.timezone', 'America/Sao_Paulo'));

            // Initialize plugins
            foreach(Config::get('plugins', []) as $plugin){
                if(!class_exists($plugin)) throw new PluginException("\"{$plugin}\" was not found");
                $plugin = new $plugin;
                $plugin->register();
            }

            // Gets the command
            array_shift(self::$args);
            if(!isset(self::$args[0])){
                self::printAscii();
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
         * Sets a custom command handler.
         * @param string $namespace Command namespace.
         * @param string $class Full command classname to be called.
         */
        public static function custom(string $namespace, string $class){
            $namespace = Util::kebabCase($namespace);
            $command = Util::pascalCase(Util::classname($class));
            self::$custom[$namespace . ':' . $command] = $class;
        }

        /**
         * Calls a Firefly command outside the terminal.
         * @param string $command Firefly command to call.
         * @param array $args (Optional) Associative array of arguments to pass with the command.
         * @param bool $silent (Optional) Disable any output from the command.
         */
        public static function call(string $command, array $args = [], bool $silent = false){
            // Register settings
            self::$args = $args;
            self::$silent = $silent;

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
                if(preg_match('/--(.+)=(.+)/', $value, $match) && count($match) == 3) $args[strtolower($match[1])] = $match[2];
            }

            // Returns the result
            self::$args = $args;
        }

        /**
         * Triggers a Firefly command.
         * @param string $command Command to trigger.
         */
        private static function triggerCommand(string $command){
            // Checks for namespaced commands
            $namespace = '';
            if(Util::stringContains($command, ':')){
                $command = explode(':', $command, 2);
                $namespace = $command[0];
                $command = $command[1];
            }

            // Parses the command and namespace properly
            $command = Util::kebabCase($command);
            $namespace = Util::kebabCase($namespace);
            self::$command = (!Util::isEmpty($namespace) ? ($namespace . ':') : '') . $command;

            // Finds a valid command
            $name = Util::pascalCase($command);
            $classname = 'Glowie\Commands\\' . (!Util::isEmpty($namespace) ? (Util::pascalCase($namespace) . '\\') : '') . $name;
            if(class_exists($classname)){
                $class = new $classname;
                $class->run();
            }else if(!empty(self::$custom[$namespace . ':' . $name]) && class_exists(self::$custom[$namespace . ':' . $name])){
                $class = new self::$custom[$namespace . ':' . $name];
                $class->run();
            }else if(is_callable([self::class, '__' . $name])){
                $name = '__' . $name;
                self::$name();
            }else{
                $command = self::$command;
                throw new ConsoleException($command, self::$args, "Unknown command \"{$command}\"");
            }
        }

        /**
         * Prints a text in the console.
         * @param string $text Text to print.
         * @param bool $break (Optional) Break line at the end.
         */
        public static function print(string $text, bool $break = true){
            if(Util::isCLI()){
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
            if(!Util::isCLI()) return $default;
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
         * Starts the local development server.
         */
        private static function __shine(){
            // Checks if CLI is running
            if(!Util::isCLI()) throw new ConsoleException(self::$command, self::$args, 'This command cannot be used from outside the console');

            // Checks if host was filled
            $host = self::getArg('host', 'localhost');

            // Checks if port was filled
            $port = self::getArg('port', 8080);

            // Starts the server
            self::print('<color="green">Local development server started!</color>');
            self::print('<color="yellow">To shutdown the server press Ctrl/Command+C</color>');
            system('php -S ' . $host . ':' . $port .' -t app/public ' . __DIR__ . '/Server.php');
        }

        /**
         * Starts the REPL interactive mode.
         */
        private static function __sandbox(){
            // Checks if CLI is running
            if(!Util::isCLI()) throw new ConsoleException(self::$command, self::$args, 'This command cannot be used from outside the console');

            // Register class alias
            foreach(Config::get('sandbox.alias', []) as $alias => $class){
                if(!class_exists($alias)) class_alias($class, $alias);
            }

            // Starts the interactive mode
            self::print('<color="green">Welcome to Firefly Sandbox!</color>');
            self::print('<color="yellow">Type quit or exit to end the interactive mode</color>');

            // REPL
            while (true) {
                // Starting tag
                self::print('<color="cyan">sandbox >> </color>', false);

                // Gets the current command
                $__command = trim(fgets(STDIN));
                if(strtolower($__command) == 'quit' || strtolower($__command) == 'exit') break;

                // Captures the output buffer
                Buffer::start();

                try {
                    // Evaluates the command
                    if(!Util::startsWith($__command, ['return', 'echo', 'print'])) $__command = 'return ' . $__command;
                    if(!Util::endsWith($__command, ';')) $__command .= ';';
                    $__returnValue = eval($__command);

                    // Flushes the buffer
                    if($__returnValue) var_dump($__returnValue);
                    $__returnText = Buffer::get();
                    if(!Util::isEmpty($__returnText)) self::print('<color="yellow">>> ' . trim($__returnText) . '</color>');
                } catch (Throwable $e) {
                    // Clears the output buffer
                    Buffer::clean();

                    // Prints the error
                    self::print('<color="red">>></color> <bg="red"><color="black">' . get_class($e) . ':</color></bg><color="red"> ' . $e->getMessage() . '</color>');
                }
            }
        }

        /**
         * Deletes all files in **app/storage/cache** folder.
         */
        private static function __clearCache(){
            $dir = Config::get('skeltch.path', Util::location('storage/cache'));
            if(!is_writable($dir)) throw new FileException('Directory "' . $dir . '" is not writable, please check your chmod settings');
            foreach (Util::getFiles($dir . '/*.tmp') as $filename) unlink($filename);
            self::print('<color="green">Cache cleared successfully!</color>');
            return true;
        }

        /**
         * Deletes all files in **app/storage/session** folder.
         */
        private static function __clearSession(){
            $dir = Config::get('session.path', Util::location('storage/session'));
            if(!is_writable($dir)) throw new FileException('Directory "' . $dir . '" is not writable, please check your chmod settings');
            foreach (Util::getFiles($dir . '/*') as $filename) unlink($filename);
            self::print('<color="green">Session data cleared successfully!</color>');
            return true;
        }

        /**
         * Clears the error log.
         */
        private static function __clearLog(){
            $file = Config::get('error_reporting.file', Util::location('storage/error.log'));
            file_put_contents($file, '');
            self::print('<color="green">Error log cleared successfully!</color>');
            return true;
        }

        /**
         * Initializes the project.
         */
        private static function __init(){
            // Creates .env file
            $file = Util::location('../.env');
            if(!is_file($file)){
                copy(Util::location('../.env.example'), $file);
                self::$silent = true;
                self::__generateKeys();
            }

            // Prints welcome message
            self::$silent = false;
            self::print('<color="magenta">
        __           _
  ___ _/ /__ _    __(_)__
 / _ `/ / _ \ |/|/ / / -_)
 \_, /_/\___/__,__/_/\__/
/___/
</color>');
            self::print('<bg="magenta"><color="black">Welcome to Glowie!</color></bg> <color="magenta">v' . Util::getVersion() . '</color>');
            self::print('<color="green">Your application is ready.</color>');
        }

        /**
         * Regenerates the application secret keys.
         */
        private static function __generateKeys(){
            // Checks permissions
            $file = Util::location('../.env');
            if(!is_writable($file)) throw new FileException('File ".env" is not writable, please check your chmod settings');

            // Reads the config file content
            $content = file_get_contents($file);

            // Generates the new keys
            $appKey = 'APP_KEY=' . Util::randomToken();
            $appToken = 'APP_TOKEN=' . Util::randomToken();
            $maintenanceKey = 'MAINTENANCE_KEY=' . Util::randomToken();

            // Replaces the new keys
            $content = preg_replace([
                '/APP_KEY=(.*)/',
                '/APP_TOKEN=(.*)/',
                '/MAINTENANCE_KEY=(.*)/'
            ], [$appKey, $appToken, $maintenanceKey], $content, 1);

            // Saves the new content
            file_put_contents($file, $content);
            self::print('<color="green">Application secret keys generated successfully!</color>');
            return true;
        }

        /**
         * Encrypts the environment config file.
         */
        private static function __encryptEnv(){
            // Reads the config file content
            $file = Util::location('../.env');
            if(!is_readable($file)) throw new FileException('File ".env" is not readable, please check your chmod settings');
            $content = file_get_contents($file);

            // Generate key and hash it
            $key = self::getArg('key', Util::randomToken());
            if(Util::isEmpty($key)) throw new ConsoleException(self::$command, self::$args, 'Missing required argument "key" for this command');
            $iv = substr($key, 0, 16);

            // Encrypts the data
            $content = openssl_encrypt($content, 'AES-256-CBC', $key, 0, $iv);

            // Saves the new content
            file_put_contents(Util::location('../.env.encrypted'), $content);
            self::print('<color="green">Environment config file encrypted successfully!</color>');
            self::print('<color="yellow">Decryption key: ' . $key . '</color>');
            self::print('<color="red">Store it with caution!</color>');
            return true;
        }

        /**
         * Decrypts the environment config file.
         */
        private static function __decryptEnv(){
            // Get key
            $key = self::argOrInput('key', 'Decryption key: ');
            if(Util::isEmpty($key)) throw new ConsoleException(self::$command, self::$args, 'Missing required argument "key" for this command');
            $iv = substr($key, 0, 16);

            // Reads the encrypted config file content
            $file = Util::location('../.env.encrypted');
            if(!is_readable($file)) throw new FileException('File ".env.encrypted" does not exist');
            $content = file_get_contents($file);

            // Decrypts the data
            $content = openssl_decrypt($content, 'AES-256-CBC', $key, 0, $iv);
            if($content === false) throw new ConsoleException(self::$command, self::$args, 'Unable to decrypt or wrong key used');

            // Saves the new content
            $targetFile = Util::location('../.env');
            file_put_contents($targetFile, $content);
            self::print('<color="green">Environment config file decrypted successfully!</color>');
            self::print('<color="cyan">File: ' . $targetFile . '</color>');
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
            if(!is_dir(Util::location('commands'))) mkdir(Util::location('commands'), 0755, true);
            if(!is_writable(Util::location('commands'))) throw new FileException('Directory "app/commands" is not writable, please check your chmod settings');

            // Checks if name was filled
            $name = self::argOrInput('name', 'Command name: ');

            // Validates the controller name
            if(Util::isEmpty($name)) throw new ConsoleException(self::$command, self::$args, 'Missing required argument "name" for this command');

            // Checks if the file exists
            $name = Util::pascalCase($name);
            $targetFile = Util::location('commands/' . $name . '.php');
            if(is_file($targetFile)) throw new ConsoleException(self::$command, self::$args, "Command {$name} already exists!");

            // Creates the file
            $template = file_get_contents(self::TEMPLATES_FOLDER . 'Command.php');
            $template = str_replace('__FIREFLY_TEMPLATE_NAME__', $name, $template);
            file_put_contents($targetFile, $template);

            // Success message
            self::print("<color=\"green\">Command {$name} created successfully!</color>");
            self::print('<color="cyan">File: ' . $targetFile . '</color>');
            return true;
        }

        /**
         * Creates a new controller.
         */
        private static function __createController(){
            // Checks permissions
            if(!is_dir(Util::location('controllers'))) mkdir(Util::location('controllers'), 0755, true);
            if(!is_writable(Util::location('controllers'))) throw new FileException('Directory "app/controllers" is not writable, please check your chmod settings');

            // Checks if name was filled
            $name = self::argOrInput('name', 'Controller name: ');

            // Validates the controller name
            if(Util::isEmpty($name)) throw new ConsoleException(self::$command, self::$args, 'Missing required argument "name" for this command');

            // Checks if the file exists
            $name = Util::pascalCase($name);
            $targetFile = Util::location('controllers/' . $name . '.php');
            if(is_file($targetFile)) throw new ConsoleException(self::$command, self::$args, "Controller {$name} already exists!");

            // Creates the file
            $template = file_get_contents(self::TEMPLATES_FOLDER . 'Controller.php');
            $template = str_replace('__FIREFLY_TEMPLATE_NAME__', $name, $template);
            file_put_contents($targetFile, $template);

            // Success message
            self::print("<color=\"green\">Controller {$name} created successfully!</color>");
            self::print('<color="cyan">File: ' . $targetFile . '</color>');
            return true;
        }

        /**
         * Creates a new language file.
         */
        private static function __createLanguage(){
            // Checks permissions
            if(!is_dir(Util::location('languages'))) mkdir(Util::location('languages'), 0755, true);
            if(!is_writable(Util::location('languages'))) throw new FileException('Directory "app/languages" is not writable, please check your chmod settings');

            // Checks if name was filled
            $name = self::argOrInput('name', 'Language name: ');

            // Validates the language id
            if(Util::isEmpty($name)) throw new ConsoleException(self::$command, self::$args, 'Missing required argument "name" for this command');

            // Checks if the file exists
            $name = trim(strtolower($name));
            $targetFile = Util::location('languages/' . $name . '.php');
            if(is_file($targetFile)) throw new ConsoleException(self::$command, self::$args, "Language file {$name} already exists!");

            // Creates the file
            copy(self::TEMPLATES_FOLDER . 'Language.php', $targetFile);

            // Success message
            self::print("<color=\"green\">Language file {$name} created successfully!</color>");
            self::print('<color="cyan">File: ' . $targetFile . '</color>');
            return true;
        }

        /**
         * Creates a new middleware.
         */
        private static function __createMiddleware(){
            // Checks permissions
            if(!is_dir(Util::location('middlewares'))) mkdir(Util::location('middlewares'), 0755, true);
            if(!is_writable(Util::location('middlewares'))) throw new FileException('Directory "app/middlewares" is not writable, please check your chmod settings');

            // Checks if name was filled
            $name = self::argOrInput('name', 'Middleware name: ');

            // Validates the middleware name
            if(Util::isEmpty($name)) throw new ConsoleException(self::$command, self::$args, 'Missing required argument "name" for this command');

            // Checks if the file exists
            $name = Util::pascalCase($name);
            $targetFile = Util::location('middlewares/' . $name . '.php');
            if(is_file($targetFile)) throw new ConsoleException(self::$command, self::$args, "Middleware {$name} already exists!");

            // Creates the file
            $template = file_get_contents(self::TEMPLATES_FOLDER . 'Middleware.php');
            $template = str_replace('__FIREFLY_TEMPLATE_NAME__', $name, $template);
            file_put_contents($targetFile, $template);

            // Success message
            self::print("<color=\"green\">Middleware {$name} created successfully!</color>");
            self::print('<color="cyan">File: ' . $targetFile . '</color>');
            return true;
        }

        /**
         * Creates a new migration.
         */
        private static function __createMigration(){
            // Checks permissions
            if(!is_dir(Util::location('migrations'))) mkdir(Util::location('migrations'), 0755, true);
            if(!is_writable(Util::location('migrations'))) throw new FileException('Directory "app/migrations" is not writable, please check your chmod settings');

            // Checks if name was filled
            $name = self::argOrInput('name', 'Migration name: ');

            // Validates the migration name
            if(Util::isEmpty($name)) throw new ConsoleException(self::$command, self::$args, 'Missing required argument "name" for this command');

            // Checks if the file exists
            $cleanName = Util::pascalCase($name);
            $name = 'm' . date('Y_m_d_His_') . $cleanName;
            $targetFile = Util::location('migrations/' . $name . '.php');
            if(is_file($targetFile)) throw new ConsoleException(self::$command, self::$args, "Migration {$cleanName} already exists!");

            // Creates the file
            $template = file_get_contents(self::TEMPLATES_FOLDER . 'Migration.php');
            $template = str_replace('__FIREFLY_TEMPLATE_NAME__', $name, $template);
            file_put_contents($targetFile, $template);

            // Success message
            self::print("<color=\"green\">Migration {$cleanName} created successfully!</color>");
            self::print('<color="cyan">File: ' . $targetFile . '</color>');
            return true;
        }

        /**
         * Creates a new model.
         */
        private static function __createModel(){
            // Checks permissions
            if(!is_dir(Util::location('models'))) mkdir(Util::location('models'), 0755, true);
            if(!is_writable(Util::location('models'))) throw new FileException('Directory "app/models" is not writable, please check your chmod settings');

            // Checks if name was filled
            $name = self::argOrInput('name', 'Model name: ');

            // Validates the model name
            if(Util::isEmpty($name)) throw new ConsoleException(self::$command, self::$args, 'Missing required argument "name" for this command');

            // Checks if table was filled
            $default_table = Util::snakeCase($name);
            $table = self::argOrInput('table', "Model table ({$default_table}): ", $default_table);
            $table = trim($table);

            // Checks if primary key was filled
            $primary = self::argOrInput('primary', 'Primary key name (id): ', 'id');
            $primary = trim($primary);

            // Checks if the file exists
            $name = Util::pascalCase($name);
            $targetFile = Util::location('models/' . $name . '.php');
            if(is_file($targetFile)) throw new ConsoleException(self::$command, self::$args, "Model {$name} already exists!");

            // Creates the file
            $template = file_get_contents(self::TEMPLATES_FOLDER . 'Model.php');
            $template = str_replace(['__FIREFLY_TEMPLATE_NAME__', '__FIREFLY_TEMPLATE_TABLE__', '__FIREFLY_TEMPLATE_PRIMARY__'], [$name, $table, $primary], $template);
            file_put_contents($targetFile, $template);

            // Success message
            self::print("<color=\"green\">Model {$name} created successfully!</color>");
            self::print('<color="cyan">File: ' . $targetFile . '</color>');
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
            foreach (glob(Util::location('migrations/*.php')) as $filename){
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
            foreach (array_reverse(glob(Util::location('migrations/*.php'))) as $filename) {
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
         * Publishes plugin files.
         */
        private static function __publish(){
            // Get force flag
            $force = filter_var(self::getArg('force', false), FILTER_VALIDATE_BOOLEAN);

            // Get plugins
            $plugins = Config::get('plugins', []);
            if(empty($plugins)){
                self::print('<color="yellow">There are no plugin files to publish.</color>');
                return false;
            }

            // Publish files
            foreach($plugins as $plugin){
                if(!class_exists($plugin)) throw new PluginException("\"{$plugin}\" was not found");
                $plugin = new $plugin;
                $plugin->publish($force);
            }

            // Print success message
            self::print('<color="green">Plugin files were published successfully.</color>');
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
            self::print('  <color="yellow">init</color> | Initializes the project');
            self::print('  <color="yellow">shine</color> <color="blue">--host --port</color> | Starts the local development server');
            self::print('  <color="yellow">sandbox</color> | Starts the REPL interactive mode');
            self::print('  <color="yellow">clear-cache</color> | Clears the application cache folder');
            self::print('  <color="yellow">clear-session</color> | Clears the application session folder');
            self::print('  <color="yellow">clear-log</color> | Clears the application error log');
            self::print('  <color="yellow">generate-keys</color> | Regenerates the application secret keys');
            self::print('  <color="yellow">encrypt-env</color> <color="blue">--key</color> | Encrypts the environment config file');
            self::print('  <color="yellow">decrypt-env</color> <color="blue">--key</color> | Decrypts the environment config file');
            self::print('  <color="yellow">test-database</color> <color="blue">--name</color> | Tests a database connection');
            self::print('  <color="yellow">create-command</color> <color="blue">--name</color> | Creates a new command for your application');
            self::print('  <color="yellow">create-controller</color> <color="blue">--name</color> | Creates a new controller for your application');
            self::print('  <color="yellow">create-language</color> <color="blue">--name</color> | Creates a new language file for your application');
            self::print('  <color="yellow">create-middleware</color> <color="blue">--name</color> | Creates a new middleware for your application');
            self::print('  <color="yellow">create-migration</color> <color="blue">--name</color> | Creates a new migration for your application');
            self::print('  <color="yellow">create-model</color> <color="blue">--name --table --primary</color> | Creates a new model for your application');
            self::print('  <color="yellow">migrate</color> <color="blue">--steps</color> | Applies pending migrations from your application');
            self::print('  <color="yellow">rollback</color> <color="blue">--steps</color> | Rolls back the last applied migration');
            self::print('  <color="yellow">publish</color> <color="blue">--force</color> | Publishes plugin files to the application folder');
            self::print('  <color="yellow">version</color> | Displays current Firefly version');
            self::print('  <color="yellow">help</color> | Displays this help message');
        }

        /**
         * Prints the Firefly logo in ASCII.
         */
        private static function printAscii(){
            self::print('<color="magenta">
    -=**********+-.
:*#*-.          .-*#+.
+%.                 :*#:
    =%-                  .##.           -++
    .%#-                  %%#*:       *#.
    :%+:+%*-              +#  .+@%**######:
-@:    .=*#*=:.        @:    :@- +# : =@
@+         .:=+****+==+@      =@ :%+*+%+
@+          :=+###*+==+@      =@ :%+*+%=
-@:    .=*#*=:.        @:    :@= +# : =@
    :%+:=#*-.             *#  .+@%**#%#*##:
    .%%-                  %%#*-       *#.
    =%-                  .##.           -*+
+%.                 :*#:
-*#+-.          .-+#+:
    .-+**********+-.</color>');
            self::print('');
        }

    }

?>