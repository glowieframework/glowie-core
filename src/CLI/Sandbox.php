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

            // Gets the current command
            $__command = self::read(Util::isEmpty(self::$continuous) ? 'sandbox >> ' : '....... >> ');

            // Handle exit command
            if ($__command === null) {
                Firefly::print(Firefly::color('Good bye!', 'green'));
                exit;
            }

            // Handle empty command
            if (Util::isEmpty($__command)) continue;

            // Fill continuous command and add command to the history
            if (!Util::isEmpty(self::$continuous)) {
                self::$continuous .= PHP_EOL . $__command;
            } else {
                self::$history[] = $__command;
                if (function_exists('readline_add_history')) readline_add_history($__command);
                self::$continuous = $__command;
            }

            // Checks if continuous command should keep capturing
            if (mb_substr_count(self::$continuous, '{') > mb_substr_count(self::$continuous, '}')) continue;

            // Ends the continuous block
            $__command = self::$continuous;
            self::$continuous = '';

            // Checks for predefined commands
            switch (explode(' ', $__command)[0]) {
                case 'history':
                    $__args = self::parseArgs($__command);
                    foreach (array_slice(self::$history, 0, -1) as $__key => $__value) {
                        if (!empty($__args['grep']) && !Util::stringContains($__value, $__args['grep'])) continue;
                        Firefly::print('<color="blue">', false);
                        Firefly::print('    ' . ($__key + 1) . ': </color><color="white">' . $__value, false);
                        Firefly::print('</color>');
                    }
                    continue 2;

                case 'exception':
                    if ($__e) {
                        HandlerCLI::exceptionHandler($__e, false);
                    } else {
                        Firefly::print(Firefly::color('No exception was thrown!', 'green'));
                    }
                    continue 2;

                case 'clear':
                case 'cls':
                    Firefly::clearScreen();
                    continue 2;

                case 'ls':
                    $__args = self::parseArgs($__command);
                    foreach (get_defined_vars() as $__key => $__value) {
                        if (in_array($__key, ['__key', '__value', '__command', '__', '__e', '__args'])) continue;
                        if (!empty($__args['grep']) && !Util::stringContains($__key, $__args['grep'])) continue;
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
                self::dump($__);
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

    /**
     * Parses the arguments from a predefined command.
     * @param string $command Command string.
     * @return array Returns the args as an associative array.
     */
    private static function parseArgs($command)
    {
        $parts = explode(' ', $command);

        // Removes the command from the args
        array_shift($parts);

        // Parses the arguments as an associative array
        $args = [];
        foreach ($parts as $value) {
            $match = [];

            // Args with values
            if (preg_match('/^--([^=]+)=(.+)$/', $value, $match)) {
                $args[mb_strtolower($match[1])] = $match[2];
            } else if (preg_match('/^-{1,2}([^=]+)$/', $value, $match)) {
                // Args without values
                $args[mb_strtolower($match[1])] = '';
            }
        }

        // Returns the result
        return $args;
    }

    /**
     * Reads a command from the user input.
     * @param string $prompt Prompt text.
     * @return string|null Returns the command if available.
     */
    private static function read($prompt)
    {
        // Checks if readline is available
        if (function_exists('readline')) {
            $command = readline($prompt);
        } else {
            // Prints the prompt string
            Firefly::print(Firefly::color($prompt, 'cyan'), false);
            $command = fgets(STDIN);
        }

        // Returns the result
        if ($command === false) return null;
        return trim($command);
    }
}
