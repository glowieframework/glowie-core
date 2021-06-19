<?php
    namespace Glowie\Core;
    
    use ErrorException;

    /**
     * Error handler for Glowie application.
     * @category Error handler
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.0
     */
    class Error{

        /**
         * Registers the error handler.
         */
        public static function register(){
            // Registers error handling functions
            error_reporting(GLOWIE_CONFIG['error_reporting']);
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            set_exception_handler([self::class, 'exceptionHandler']);
            set_error_handler([self::class, 'errorHandler']);
            ini_set('display_errors', 'Off');
            ini_set('display_startup_errors', 'Off');

            // Sets syntax highliter style
            ini_set('highlight.comment', '#8BC34A');
            ini_set('highlight.default', '#545454');
            ini_set('highlight.html', '#06B');
            ini_set('highlight.keyword', '#FF5722');
            ini_set('highlight.string', '#4CAF50');
        }

        /**
         * Default error handler. Emulates an exception based in a given error.
         * @param int $level Error level code.
         * @param string $str Error message.
         * @param string $file (Optional) Filename where the error was thrown.
         * @param int $line (Optional) Line number where the error was thrown.
         */
        public static function errorHandler(int $level, string $str, string $file = '', int $line = 0){
            self::exceptionHandler(new ErrorException($str, 0, $level, $file, $line));
            return false;
        }

        /**
         * Exception handler.
         * @param Exception $e Thrown exception.
         */
        public static function exceptionHandler($e){
            // Error logging
            $date = date('Y-m-d H:i:s');
            self::log("[{$date}] {$e->getMessage()} at file {$e->getFile()}:{$e->getLine()}\n{$e->getTraceAsString()}\n\n");

            // Display the error
            http_response_code(500);
            if(error_reporting()){
                echo '
                <div style="clear: both; font-family: Segoe UI, sans-serif; font-size: 18px; background-color: white; color: black; margin: 10px; border: 1px solid #d04978;">
                    <div style="background-color: #e25787; color: white; padding: 10px 20px;">
                        <strong>Oops! An error has ocurred:</strong> ' . $e->getMessage() . '
                    </div>
                    <div style="padding: 20px;">
                        <i style="word-wrap: break-word; color: dimgray; display: block; font-size: 16px;">File: <b style="color: #ed578b;">' . $e->getFile() . '</b> at line <b style="color: #ed578b;">' . $e->getLine() . '</b>.</i>
                        <span style="font-size: 14px; color: gray; display: block;">Exception thrown in ' . self::getExceptionTime() . ' seconds.</span>' .
                        self::highlight($e->getFile(), $e->getLine()) .
                        self::parseTrace($e->getTrace()) .
                    '</div>
                </div>';
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

            // Parses the code
            $text = trim($text[$line - 1]);
            $text = highlight_string("<?php " . $text, true);
            $text = trim($text);
            $text = preg_replace("|^\\<code\\>\\<span style\\=\"color\\: #[a-fA-F0-9]{0,6}\"\\>|", "", $text, 1);
            $text = preg_replace("|\\</code\\>\$|", "", $text, 1);
            $text = trim($text);
            $text = preg_replace("|\\</span\\>\$|", "", $text, 1);
            $text = trim($text);
            $text = preg_replace("|^(\\<span style\\=\"color\\: #[a-fA-F0-9]{0,6}\"\\>)(&lt;\\?php&nbsp;)(.*?)(\\</span\\>)|", "\$1\$3\$4", $text);

            // Returns resulting block
            return '<code style="white-space: wrap; word-wrap: break-word; font-size: 16px; display: block; border: 1px solid gainsboro; margin: 20px 0; background-color: #f5f5f5; padding: 15px;">
                        <span style="color: #75715E;">' . $line . '</span> ' . $text . '</span>
                    </code>';
        }

        /**
         * Parses the stack trace to a table.
         * @param array $trace Stack trace array.
         * @return string Table result in HTML.
         */
        private static function parseTrace($trace){
            $isTraceable = false;
            $result =    '<strong style="color: #ed578b;">Stack trace:</strong>
                        <table cellspacing="0" cellpadding="0" style="width: 100%; table-layout: fixed; margin-top: 10px;"><tbody>';
            foreach($trace as $key => $item){
                if(!empty($item['class']) && $item['class'] == self::class) continue;
                if(!$isTraceable) $isTraceable = true;
                $result .=   '<tr>' .
                                // Index
                                '<td style="border: 1px solid lightgray; padding: 10px; vertical-align: top; text-align: center; font-weight: bold; color: #ed578b; width: 40px;">#' . ($key + 1) .'</td>' .
                                '<td style="border: 1px solid lightgray; padding: 10px; word-break: break-all;">' .
                                    // File/line
                                    (!empty($item['file']) && !empty($item['line']) ? '<i style="color: dimgray; display: block; font-size: 14px;">' . $item['file'] . ':' . $item['line'] . '</i>' : '') .
                                    
                                    // Class
                                    (!empty($item['class']) ? '<span style="color: #d2024a; font-weight: 600;">' . $item['class'] . '</span>-><span style="color: #ed578b">' . $item['function'] . '()</span>' : '') . 
                                    
                                    // Args
                                    (!empty($item['args']) ? '<i style="font-size: 14px; font-weight: 600; display: block; margin: 10px 0;">Args:</i><pre style="white-space: pre-wrap; word-wrap: break-all; background-color: #f5f5f5; border: 1px solid gainsboro; padding: 15px; margin: 0;">' . print_r($item['args'], true) . '</pre>' : '') . '
                                </td>
                            </tr>';
            }
            $result .= '</tbody></table>';
            return $isTraceable ? $result : '';
        }

        /**
         * Logs the error to the error.log file.
         * @param string $content Content to append to the file.
         */
        private static function log(string $content){
            if(!GLOWIE_CONFIG['error_log']) return;
            if(!is_writable('../storage')) die('Error: Directory "app/storage" is not writable, please check your chmod settings');
            file_put_contents('../storage/error.log', $content, FILE_APPEND);
        }

        /**
         * Returns the page exception time.
         * @return float Exception time.
         */
        private static function getExceptionTime(){
            return round((microtime(true) - GLOWIE_START_TIME), 5);
        }

    }

?>