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
 * @link https://gabrielsilva.dev.br/glowie
 * @see https://gabrielsilva.dev.br/glowie/docs/latest/extra/cli
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
        Firefly::print('<color="green">Welcome to Firefly Sandbox!</color>');
        Firefly::print('<color="yellow">Type quit or exit to end the interactive mode</color>');

        // REPL
        while (true) {
            // Inject last exception and last result
            $__e = self::$exception;
            $__ = self::$result;

            // Starting tag
            Firefly::print('<color="cyan">sandbox >> </color>', false);

            // Gets the current command
            $__command = trim(fgets(STDIN));
            if (Util::isEmpty($__command)) continue;

            // Add command to the history
            self::$history[] = $__command;

            // Fill continuous command
            if (!Util::isEmpty(self::$continuous)) $__command = self::$continuous .= "\n" . $__command;

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
                        Firefly::print('<color="green">No exception was thrown.</color>');
                    }
                    continue 2;

                case 'clear':
                case 'cls':
                    Firefly::clearScreen();
                    continue 2;

                case 'ls':
                    foreach (get_defined_vars() as $__key => $__value) {
                        if (in_array($__key, ['__key', '__value'])) continue;
                        Firefly::print('<color="magenta">', false);
                        Firefly::print('>> $' . $__key . ' = ', false);

                        Buffer::start();
                        Util::dump($__value, false, true);
                        $__value = Buffer::get();

                        Firefly::print(trim($__value));
                    }

                    Firefly::print('</color>', false);
                    continue 2;

                case 'quit':
                case 'exit':
                    Firefly::print('<color="green">Good bye!</color>');
                    exit;

                default:
                    break;
            }

            // Clear previous variables
            if (isset($__key)) unset($__key);
            if (isset($__value)) unset($__value);

            // Checks for continuous command
            if (Util::endsWith($__command, ['{', '(', '[', '\\'])) {
                if (Util::endsWith($__command, '\\')) $__command = rtrim($__command, '\\');
                self::$continuous .= $__command;
                continue;
            } else {
                self::$continuous = '';
            }

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
                if (!Util::startsWith($__command, ['return', 'echo', 'print'])) $__command = 'return ' . $__command;
                if (!Util::endsWith($__command, ';')) $__command .= ';';
                $__ = eval($__command);

                // Flushes the buffer
                if ($__) Util::dump($__, false, true);
                $__ = Buffer::get();

                // Prints the result
                self::$result = trim($__);
                Firefly::print('<color="yellow">>> ' . self::$result . '</color>');
            } catch (Throwable $__e) {
                // Clears the output buffer
                Buffer::clean();

                // Saves the current exception
                self::$exception = $__e;

                // Prints the error
                Firefly::print('<color="red">>></color> <bg="red"><color="black">' . get_class($__e) . ':</color></bg><color="red"> ' . $__e->getMessage() . '</color>');
            }
        }
    }
}
