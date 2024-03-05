<?php
    namespace Glowie\Core\CLI;

    use Glowie\Core\Exception\ConsoleException;
    use Util;

    /**
     * CLI command core for Glowie application.
     * @category Command
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) Glowie
     * @license MIT
     * @link https://gabrielsilva.dev.br/glowie
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
            Firefly::table($headers, $rows);
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
            Firefly::clearScreen();
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
         * Asks for the user input in the console, but text will be hidden.
         * @param string $message (Optional) Message to prompt to the user.
         * @param string $default (Optional) Default value to return if no input is provided.
         * @return string Returns the input value as a string.
         */
        public function inputPassword(string $message = '', string $default = ''){
            $response = $this->input($message . '<hidden>', $default);
            $this->print('</hidden>', false);
            return $response;
        }

        /**
         * Prompts the user to choose between a list of options.
         * @param string $message (Optional) Message to prompt to the user.
         * @param array $options Array of available options. **Options are indexed starting with 1 instead of 0.**
         * @param int $default (Optional) Default response to return if empty.
         * @return mixed Returns the selected option value if valid, null otherwise.
         */
        public function select(string $message = '', array $options, int $default = 1){
            // Validate args
            if(empty($options)) throw new ConsoleException(Firefly::getCommand(), Firefly::getArgs(), 'select(): Options array cannot be empty');
            if(!isset($options[$default - 1])) throw new ConsoleException(Firefly::getCommand(), Firefly::getArgs(), 'select(): Invalid default value "' . $default . '"');
            $options = array_values($options);

            // Create prompt
            $this->print($message);
            $this->print('<color="magenta">', false);
            foreach($options as $key => $item) $this->print('  ' . ($key + 1) . ': ' . $item);
            $this->print('</color>', false);
            $response = (int)$this->input('<color="yellow">[1-' . count($options) . '] </color>', $default);

            // Return response
            if(!isset($options[$response - 1])) return null;
            return $options[$response - 1];
        }

        /**
         * Prompts the user to confirm an action with yes or no.
         * @param string $message (Optional) Message to prompt to the user.
         * @param bool $default (Optional) Default response to return if empty.
         * @return bool Returns true or false, depending on the user answer, or the default value if invalid answer.
         */
        public function confirm(string $message = '', bool $default = false){
            $message .= '<color="yellow"> [y/n] </color>';
            $response = $this->input($message, $default ? 'y' : 'n');

            switch(trim(strtolower($response))){
                case 'y':
                case 'yes':
                case 'true':
                case '1':
                    return true;
                    break;

                case 'n':
                case 'no':
                case 'false':
                case '0':
                    return false;
                    break;

                default:
                    return $default;
                    break;
            }
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