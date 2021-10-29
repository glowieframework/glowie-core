<?php
    namespace Glowie\Core\CLI;

    /**
     * CLI command core for Glowie application.
     * @category Command
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.0
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
         * @param array $rows Data to parse. Must be an array of associative or numeric indexed arrays.
         * @param array $headers Column headers to set in the table. When working with associative arrays of data,\
         * must also contain the key of each column you want to parse.
         */
        public function table(array $rows, array $headers){
            Firefly::table($rows, $headers);
        }

        /**
         * Prints one or more blank lines in the console.
         * @param int $number (Optional) Number of blank lines to print.
         */
        public function line(int $number = 1){
            for($i = 0; $i < $number; $i++) Firefly::print('');
        }

        /**
         * Prints a success text in the console.
         * @param string $text Text to print.
         * @param bool $break (Optional) Break line at the end.
         */
        public function success(string $text, bool $break = true){
            Firefly::print('<color="green">' . $text . '</color>', $break);
        }
        
        /**
         * Prints a fail text in the console.
         * @param string $text Text to print.
         * @param bool $break (Optional) Break line at the end.
         */
        public function fail(string $text, bool $break = true){
            Firefly::print('<color="red">' . $text . '</color>', $break);
        }

        /**
         * Prints a warning text in the console.
         * @param string $text Text to print.
         * @param bool $break (Optional) Break line at the end.
         */
        public function warning(string $text, bool $break = true){
            Firefly::print('<color="yellow">' . $text . '</color>', $break);
        }
        
        /**
         * Prints an info text in the console.
         * @param string $text Text to print.
         * @param bool $break (Optional) Break line at the end.
         */
        public function info(string $text, bool $break = true){
            Firefly::print('<color="cyan">' . $text . '</color>', $break);
        }

        /**
         * Prints an error text in the console.
         * @param string $text Text to print.
         * @param bool $break (Optional) Break line at the end.
         */
        public function error(string $text, bool $break = true){
            Firefly::print('<bg="red"><color="black">' . $text . '</color></bg>', $break);
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

    }

?>