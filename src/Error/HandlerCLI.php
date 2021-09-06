<?php
    namespace Glowie\Core\Error;

    use Glowie\Core\CLI\Firefly;
    use Glowie\Core\Config;
    use ErrorException;

    /**
     * CLI error handler for Glowie application.
     * @category Error handler
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.0
     */
    class HandlerCLI{

        /**
         * Registers the error handlers for CLI.
         */
        public static function register(){
            $level = Config::get('error_reporting', E_ALL);
            error_reporting($level);
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            set_exception_handler([self::class, 'exceptionHandler']);
            set_error_handler([self::class, 'errorHandler'], $level);
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
        }

        /**
         * Default CLI error handler. Throws an exception based in a given error.
         * @param int $level Error level.
         * @param string $message Error message.
         * @param string $file (Optional) Filename where the error occurred.
         * @param int $line (Optional) Line number where the error was triggered.
         */
        public static function errorHandler(int $level, string $message, ?string $file = '', ?int $line = 0){
            throw new ErrorException($message, 0, $level, $file, $line);
            return true;
        }

        /**
         * CLI exception handler.
         * @param Exception $e Thrown exception.
         */
        public static function exceptionHandler($e){
            // Error logging
            $date = date('Y-m-d H:i:s');
            self::log("[{$date}] {$e->getMessage()} at file {$e->getFile()}:{$e->getLine()}\n{$e->getTraceAsString()}\n\n");

            // Display the error
            if(error_reporting()){
                Firefly::print('');
                Firefly::print('<bg="red"><color="black">Oops! An error has ocurred:</color></bg> <color="red">' . $e->getMessage() . '</color>');
                Firefly::print('<color="blue">' . get_class($e) . '</color>');
                Firefly::print('');
                Firefly::print('File: <color="yellow">' . $e->getFile() . '</color> at line <color="yellow">' . $e->getLine() . '</color>.');
                Firefly::print('<color="red">Exception thrown in ' . self::getExceptionTime() . '</color>');
                Firefly::print('');
                Firefly::print(self::highlight($e->getFile(), $e->getLine()));
                Firefly::print('');
                Firefly::print('<color="green">Stack trace:</color>');
                Firefly::print($e->getTraceAsString());
            }else{
                Firefly::print('<bg="red"><color="black">An error has ocurred.</color></bg>');
            }
        }

        /**
         * Highlights a single line from a PHP file.
         * @param string $file File path.
         * @param int $line Line to highlight.
         * @return string Highlighted result in HTML.
         */
        private static function highlight(string $file, int $line){
            // Checks for the line
            if(!is_readable($file)) return '';
            $text = file($file, FILE_IGNORE_NEW_LINES);
            if($text === false) return '';
            if(empty($text[$line - 1])) return '';

            // Returns the result
            return '    <color="magenta">' . $line . '</color><color="cyan">  ' . trim($text[$line - 1]) . '</color>';
        }

        /**
         * Logs the error to the error.log file.
         * @param string $content Content to append to the file.
         */
        private static function log(string $content){
            if(!Config::get('error_log', true)) return;
            if(!is_writable(Firefly::getAppFolder() . 'storage')){
                Firefly::print('<bg="red"><color="black">Unable to log errors!</color></bg>');
                Firefly::print('<color="red">Directory "app/storage" is not writable, please check your chmod settings</color>');
                die();
            }
            file_put_contents(Firefly::getAppFolder() . 'storage/error.log', $content, FILE_APPEND);
        }

        /**
         * Returns the page exception time.
         * @return float Exception time.
         */
        private static function getExceptionTime(){
            return round((microtime(true) - APP_START_TIME) * 1000, 2) . 'ms';
        }

    }


?>