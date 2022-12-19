<?php
    namespace Glowie\Core\CLI;

    use Util;

    /**
     * CLI command core for Glowie application.
     * @category Command
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) Glowie
     * @license MIT
     * @link https://glowie.tk
     */
    abstract class Command{

        /**
         * The command handler.
         */
        public abstract function run();

        /**
         * Prints a text in the console.
         * @param string $text Text to print.
         * @param bool $break (Optional) Break line at the end.
         */
        public function print(string $text, bool $break = true){
            Firefly::print($text, $break);
        }

        /**
         * Prints a table of data in the console.
         * @param array $headers Table headers.
         * @param array $rows A multi-dimensional array of data to parse.
         */
        public function table(array $headers, array $rows){
            // Remove associative indexes from the arrays
            $headers = array_values($headers);
            foreach($rows as $key => $row) $rows[$key] = array_values((array)$row);

            // Parse maximum column sizes
            $maxSizes = [];
            $grid = [];

            foreach($headers as $key => $col){
                $maxSizes[$key] = mb_strlen($col);

                // Find cells
                foreach(array_column($rows, $key) as $row){
                    $row = (string)$row;
                    if(mb_strlen($row) > $maxSizes[$key]) $maxSizes[$key] = mb_strlen($row);
                }

                // Parse grid
                $grid[] = '+' . str_repeat('-', $maxSizes[$key] + 2);
            }

            // Create the table
            $table = [];
            foreach(array_merge([$headers], $rows) as $key => $row){
                // Fill empty values
                $row = array_pad($row, count($headers), '');
                foreach($row as $cellKey => $cell){
                    if(!isset($maxSizes[$cellKey])) continue;
                    $table[$key][] = str_pad((string)$cell, $maxSizes[$cellKey], ' ');
                }
            }

            // Print top grid
            $grid = implode('', $grid) . '+';
            $this->print($grid);

            // Print rows
            foreach($table as $row){
                $this->print('| ' . implode(' | ', $row) . ' |');
                $this->print($grid);
            }
        }

        /**
         * Prints one or more blank lines in the console.
         * @param int $number (Optional) Number of blank lines to print.
         */
        public function line(int $number = 1){
            $this->print(str_repeat(PHP_EOL, $number), false);
        }

        /**
         * Clears the current console line.
         */
        public function clear(){
            if(Util::isCLI()) $this->print("\033[2K\r", false);
        }

        /**
         * Clears the whole console screen.
         */
        public function clearScreen(){
            if(Util::isCLI()) DIRECTORY_SEPARATOR === '\\' ? popen('cls', 'w') : exec('clear');
        }

        /**
         * Prints a progress bar in the console.
         * @param int|bool $step Current step. Set to **false** to clear the whole progress bar.
         * @param int $total (Optional) Total number of steps.
         * @param int $size (Optional) Progress bar size.
         */
        public function progress($step, int $total = 100, int $size = 30){
            // Check to clear progress bar
            if($step === false) return $this->clear();

            // Calculate the progress
            $progress = (int)(($step / $total) * 100);
            $step = (int)(($progress * $size) / 100);

            // Print the bar
            $bar = '[' . str_pad(str_repeat('=', $step), $size, ' ') . '] ' . $progress . '%';
            $this->clear();
            $this->print($bar, false);
        }

        /**
         * Prints a success text in the console.
         * @param string $text Text to print.
         * @param bool $break (Optional) Break line at the end.
         */
        public function success(string $text, bool $break = true){
            $this->print('<color="green">' . $text . '</color>', $break);
        }

        /**
         * Prints a fail text in the console.
         * @param string $text Text to print.
         * @param bool $break (Optional) Break line at the end.
         */
        public function fail(string $text, bool $break = true){
            $this->print('<color="red">' . $text . '</color>', $break);
        }

        /**
         * Prints a warning text in the console.
         * @param string $text Text to print.
         * @param bool $break (Optional) Break line at the end.
         */
        public function warning(string $text, bool $break = true){
            $this->print('<color="yellow">' . $text . '</color>', $break);
        }

        /**
         * Prints an info text in the console.
         * @param string $text Text to print.
         * @param bool $break (Optional) Break line at the end.
         */
        public function info(string $text, bool $break = true){
            $this->print('<color="cyan">' . $text . '</color>', $break);
        }

        /**
         * Prints an error text in the console.
         * @param string $text Text to print.
         * @param bool $break (Optional) Break line at the end.
         */
        public function error(string $text, bool $break = true){
            $this->print('<bg="red"><color="black">' . $text . '</color></bg>', $break);
        }

        /**
         * Asks for the user input in the console.
         * @param string $message (Optional) Message to prompt to the user.
         * @param string $default (Optional) Default value to return if no input is provided.
         * @return string Returns the input value as a string.
         */
        public function input(string $message = '', string $default = ''){
            return Firefly::input($message, $default);
        }

        /**
         * Checks if an argument has been passed, otherwise asks for the user input in the console.
         * @param string $arg Argument name to check. If passed, its value will be returned.
         * @param string $message (Optional) Message to prompt to the user if the argument was not passed.
         * @param string $default (Optional) Default value to return if the argument is not passed or no input is provided.
         * @return string Returns the value as a string.
         */
        public function argOrInput(string $arg, string $message = '', string $default = ''){
            return Firefly::argOrInput($arg, $message, $default);
        }

        /**
         * Checks if an argument has been passed, otherwise throws an exception.
         * @param string $arg Argument name to check. If passed, its value will be returned.
         * @return string Returns the value as a string if the argument was passed.
         * @throws ConsoleException Throws an exception if the argument was not passed.
         */
        public function argOrFail(string $arg){
            return Firefly::argOrFail($arg);
        }

        /**
         * Gets an argument value.
         * @param string $arg Argument key to get.
         * @param mixed $default (Optional) Default value to return if the key does not exist.
         * @return mixed Returns the value if exists or the default if not.
         */
        public function getArg(string $key, $default = null){
            return Firefly::getArg($key, $default);
        }

        /**
         * Gets all arguments as an associative array.
         * @return array Returns an array of arguments.
         */
        public function getArgs(){
            return Firefly::getArgs();
        }

        /**
         * Halts the execution in milliseconds.
         * @param int $ms Time to wait in ms.
         */
        public function wait(int $ms){
            usleep(ceil($ms) * 1000);
        }

    }

?>