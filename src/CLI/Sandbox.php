<?php
    namespace Glowie\Core\CLI;

    use Glowie\Core\View\Buffer;
    use Glowie\Core\Exception\ConsoleException;
    use Throwable;
    use Util;
    use Config;

    /**
     * REPL for Glowie application.
     * @category CLI
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) Glowie
     * @license MIT
     * @link https://eugabrielsilva.tk/glowie
     */
    class Sandbox{

        /**
         * History of commands.
         * @var array
         */
        private static $__history = [];

        /**
         * Last exception.
         * @var Throwable
         */
        private static $__exception;

        /**
         * Run the CLI Sandbox.
         */
        public static function run(){
            // Checks if CLI is running
            if(!Util::isCLI()) throw new ConsoleException(Firefly::getCommand(), Firefly::getArgs(), 'This command cannot be used from outside the console');

            // Register class alias
            foreach(Config::get('sandbox.alias', []) as $alias => $class){
                if(!class_exists($alias)) class_alias($class, $alias);
            }

            // Starts the interactive mode
            Firefly::print('<color="green">Welcome to Firefly Sandbox!</color>');
            Firefly::print('<color="yellow">Type quit or exit to end the interactive mode</color>');

            // REPL
            while (true) {
                // Starting tag
                Firefly::print('<color="cyan">sandbox >> </color>', false);

                // Gets the current command
                $__command = trim(fgets(STDIN));

                // Add command to the history
                self::$__history[] = $__command;

                // Checks for predefined commands
                if(self::handleCommands($__command)) continue;

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
                    if(!Util::isEmpty($__returnText)) Firefly::print('<color="yellow">>> ' . trim($__returnText) . '</color>');
                } catch (Throwable $e) {
                    // Clears the output buffer
                    Buffer::clean();

                    // Saves the current exception
                    self::$__exception = $e;

                    // Prints the error
                    Firefly::print('<color="red">>></color> <bg="red"><color="black">' . get_class($e) . ':</color></bg><color="red"> ' . $e->getMessage() . '</color>');
                }
            }
        }

        public static function handleCommands($__command){
            // Prepare command
            $__command = strtolower($__command);

            // Get each predefined command
            switch($__command){
                case 'history':
                    foreach(self::$__history as $__historyKey => $__historyItem){
                        Firefly::print('<color="magenta">' . ($__historyKey + 1) . ':</color> ' . $__historyItem);
                    }
                    return true;

                case 'exception':
                    if(self::$__exception){
                        //
                    }
                    return true;

                case 'clear':
                case 'cls':
                    Firefly::clearScreen();
                    return true;

                case 'quit':
                case 'exit':
                    exit;

                default:
                    return false;
            }
        }

    }
?>