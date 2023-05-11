<?php
    namespace Glowie\Core\CLI;

    use Glowie\Core\View\Buffer;
    use Glowie\Core\Exception\ConsoleException;
    use Glowie\Core\Error\HandlerCLI;
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
         * Private scope of variables.
         * @var array
         */
        private static $__scope = [
            'history' => [],
            'exception' => null
        ];

        /**
         * Run the CLI Sandbox.
         */
        public static function run(){
            // Checks if CLI is running
            if(!Util::isCLI()) throw new ConsoleException(Firefly::getCommand(), Firefly::getArgs(), 'This command cannot be used from outside the console');

            // Register class alias
            foreach(Config::get('sandbox.alias', []) as self::$__scope['key'] => self::$__scope['value']){
                if(!class_exists(self::$__scope['key'])) class_alias(self::$__scope['value'], self::$__scope['key']);
            }

            // Starts the interactive mode
            Firefly::print('<color="green">Welcome to Firefly Sandbox!</color>');
            Firefly::print('<color="yellow">Type quit or exit to end the interactive mode</color>');

            // REPL
            while (true) {
                // Starting tag
                Firefly::print('<color="cyan">sandbox >> </color>', false);

                // Gets the current command
                self::$__scope['command'] = trim(fgets(STDIN));
                if(Util::isEmpty(self::$__scope['command'])) continue;

                // Add command to the history
                self::$__scope['history'][] = self::$__scope['command'];

                // Checks for predefined commands
                switch(self::$__scope['command']){
                    case 'history':
                        Firefly::print('<color="blue">', false);

                        foreach(self::$__scope['history'] as self::$__scope['key'] => self::$__scope['value']){
                            Firefly::print((self::$__scope['key'] + 1) . ': ' . self::$__scope['value']);
                        }

                        Firefly::print('</color>', false);
                        continue 2;

                    case 'exception':
                        if(self::$__scope['exception']) HandlerCLI::exceptionHandler(self::$__scope['exception']);
                        continue 2;

                    case 'clear':
                    case 'cls':
                        Firefly::clearScreen();
                        continue 2;

                    case 'ls':
                        Firefly::print('<color="magenta">', false);

                        foreach(get_defined_vars() as self::$__scope['key'] => self::$__scope['value']){
                            if(self::$__scope['key'] == '__exception') continue;

                            Firefly::print('>> $' . self::$__scope['key'] . ' = ', false);

                            Buffer::start();
                            var_dump(self::$__scope['value']);
                            self::$__scope['value'] = Buffer::get();

                            Firefly::print(trim(self::$__scope['value']));
                        }

                        Firefly::print('</color>', false);
                        continue 2;

                    case 'quit':
                    case 'exit':
                        exit;

                    default:
                        break;
                }

                // Checks for console command
                if(Util::startsWith(self::$__scope['command'], '`') && Util::endsWith(self::$__scope['command'], '`')){
                    self::$__scope['command'] = Util::replaceFirst(self::$__scope['command'], '`', '');
                    self::$__scope['command'] = Util::replaceLast(self::$__scope['command'], '`', '');

                    passthru(self::$__scope['command']);
                    continue;
                }

                // Captures the output buffer
                Buffer::start();

                try {
                    // Evaluates the command
                    if(!Util::startsWith(self::$__scope['command'], ['return', 'echo', 'print'])) self::$__scope['command'] = 'return ' . self::$__scope['command'];
                    if(!Util::endsWith(self::$__scope['command'], ';')) self::$__scope['command'] .= ';';
                    self::$__scope['return'] = eval(self::$__scope['command']);

                    // Flushes the buffer
                    if(self::$__scope['return']) var_dump(self::$__scope['return']);
                    self::$__scope['return'] = Buffer::get();
                    if(!Util::isEmpty(self::$__scope['return'])) Firefly::print('<color="yellow">>> ' . trim(self::$__scope['return']) . '</color>');
                } catch (Throwable $__exception) {
                    // Clears the output buffer
                    Buffer::clean();

                    // Saves the current exception
                    self::$__scope['exception'] = $__exception;

                    // Prints the error
                    Firefly::print('<color="red">>></color> <bg="red"><color="black">' . get_class($__exception) . ':</color></bg><color="red"> ' . $__exception->getMessage() . '</color>');
                }
            }
        }

    }
?>