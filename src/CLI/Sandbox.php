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
 * @link https://glowie.gabrielsilva.dev.br
 * @see https://glowie.gabrielsilva.dev.br/docs/latest/extra/cli
 */
class Sandbox
{

    /**
     * Command history.
     * @var array
     */
    private static $history = [];

    /**
     * Last thrown exception.
     * @var Throwable|null
     */
    private static $exception = null;

    /**
     * Handler for continuous commands.
     * @var string
     */
    private static $continuous = '';

    /**
     * Last result.
     * @var string
     */
    private static $result = '';

    /**
     * Run the CLI Sandbox.
     */
    public static function run()
    {
        // Checks if CLI is running
        if (!Util::isCLI()) throw new ConsoleException(Firefly::getCommand(), Firefly::getArgs(), 'This command cannot be used from outside the console');

        // Register class alias
        foreach (Config::get('sandbox.alias', []) as $__key => $__value) {
            if (!class_exists($__key)) class_alias($__value, $__key);
        }

        // Starts the interactive mode
        Firefly::print(Firefly::color('Welcome to Firefly Sandbox!', 'green'));
        Firefly::print(Firefly::color('Type quit or exit to end the interactive mode', 'yellow'));

        // REPL
        while (true) {
            // Inject last exception and last result
            $__e = self::$exception;
            $__ = self::$result;

            // Starting tag
            Firefly::print(Firefly::color(Util::isEmpty(self::$continuous) ? 'sandbox >> ' : '....... >> ', 'cyan'), false);

            // Gets the current command
            $__command = trim(fgets(STDIN));
            if (Util::isEmpty($__command)) continue;

            // Fill continuous command and add command to the history
            if (!Util::isEmpty(self::$continuous)) {
                self::$continuous .= PHP_EOL . $__command;
            } else {
                self::$history[] = $__command;
                self::$continuous = $__command;
            }

            // Checks if continuous command should keep capturing
            if (mb_substr_count(self::$continuous, '{') > mb_substr_count(self::$continuous, '}')) continue;

            // Ends the continuous block
            $__command = self::$continuous;
            self::$continuous = '';

            // Checks for predefined commands
            switch ($__command) {
                case 'history':
                    foreach (self::$history as $__key => $__value) {
                        Firefly::print('<color="blue">', false);
                        Firefly::print('    ' . ($__key + 1) . ': </color><color="white">' . $__value, false);
                        Firefly::print('</color>');
                    }
                    continue 2;

                case 'exception':
                    if ($__e) {
                        HandlerCLI::exceptionHandler($__e);
                    } else {
                        Firefly::print(Firefly::color('No exception was thrown!', 'green'));
                    }
                    continue 2;

                case 'clear':
                case 'cls':
                    Firefly::clearScreen();
                    continue 2;

                case 'ls':
                    foreach (get_defined_vars() as $__key => $__value) {
                        if (in_array($__key, ['__key', '__value', '__command', '__', '__e'])) continue;
                        Firefly::print('<color="magenta">', false);
                        Firefly::print('>> $' . $__key . ' = ', false);

                        Buffer::start();
                        self::dump($__value);
                        $__value = Buffer::get();

                        Firefly::print($__value);
                    }

                    Firefly::print('</color>', false);
                    continue 2;

                case 'quit':
                case 'exit':
                    Firefly::print(Firefly::color('Good bye!', 'green'));
                    exit;

                default:
                    break;
            }

            // Clear previous variables
            if (isset($__key)) unset($__key);
            if (isset($__value)) unset($__value);

            // Checks for console command
            if (Util::startsWith($__command, '`') && Util::endsWith($__command, '`')) {
                $__command = Util::replaceFirst($__command, '`', '');
                $__command = Util::replaceLast($__command, '`', '');
                passthru($__command);
                continue;
            }

            // Captures the output buffer
            Buffer::start();

            try {
                // Evaluates the command
                if (!Util::startsWith($__command, ['return', 'echo', 'print', 'if', 'for', 'while', 'foreach', 'function', 'class', 'switch', 'do', 'try'])) $__command = 'return ' . $__command;
                if (!Util::endsWith($__command, ';')) $__command .= ';';
                $__ = eval($__command);

                // Flushes the buffer
                if ($__) self::dump($__);
                $__ = Buffer::get();

                // Prints the result
                self::$result = trim($__);
                Firefly::print(Firefly::color('>> ' . self::$result, 'yellow'));
            } catch (Throwable $__e) {
                // Clears the output buffer
                Buffer::clean();

                // Saves the current exception
                self::$exception = $__e;

                // Prints the error
                Firefly::print(sprintf(
                    '%s %s %s',
                    Firefly::color('>>', 'red'),
                    Firefly::bg(Firefly::color(get_class($__e) . ':', 'black'), 'red'),
                    Firefly::color($__e->getMessage(), 'red')
                ));
            }
        }
    }

    /**
     * Captures the result of a variable dump.
     * @param mixed $var Variable to be dumped.
     */
    private static function dump($var)
    {
        $dump = Util::parseDump($var, true);
        Firefly::print($dump . '</color>', false);
    }
}
