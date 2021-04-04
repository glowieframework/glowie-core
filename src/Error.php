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
     * @version 0.3-alpha
     */
    class Error{

        /**
         * Initializes the error handler.
         */
        public static function init(){
            // Registers error handling functions
            error_reporting($GLOBALS['glowieConfig']['error_reporting']);
            if(error_reporting()) mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            set_exception_handler(['Glowie\Core\Error', 'exceptionHandler']);
            register_shutdown_function(['Glowie\Core\Error', 'fatalHandler']);
            set_error_handler(['Glowie\Core\Error', 'errorHandler']);
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
         * Default error handler. Throws an exception based in a given error.
         * @param int $level Error level code.
         * @param string $str Error message.
         * @param string $file (Optional) Filename where the error was thrown.
         * @param int $line (Optional) Line number where the error was thrown.
         */
        public static function errorHandler(int $level, string $str, string $file = '', int $line = 0){
            if(error_reporting() & $level){
                self::exceptionHandler(new ErrorException($str, 0, $level, $file, $line));
                http_response_code(500);
                exit();
            }
        }
        
        /**
         * Fatal error handler.
         */
        public static function fatalHandler(){
            $error = error_get_last();
            if ($error && $error["type"] == E_ERROR) self::errorHandler($error["type"], $error["message"], $error["file"], $error["line"]);
            http_response_code(500);
            exit();
        }

        /**
         * Exception handler.
         * @param Exception $e Thrown exception.
         */
        public static function exceptionHandler($e){
            echo '
                <div style="clear: both; font-family: Segoe UI, sans-serif; font-size: 18px; background-color: white; color: black; margin: 10px; border: 1px solid #d04978;">
                    <div style="background-color: #e25787; color: white; padding: 10px 20px;">
                        <strong>Oops! An error has ocurred:</strong> ' . $e->getMessage() . '
                    </div>
                    <div style="padding: 20px;">
                        <i style="color: dimgray; display: block; font-size: 16px;">File: <b style="color: #ed578b;">' . $e->getFile() . '</b> at line <b style="color: #ed578b;">' . $e->getLine() . '</b>.</i>
                        <span style="font-size: 14px; color: gray; display: block;">Exception thrown in ' . self::getExceptionTime() . ' seconds.</span>' .
                        self::highlight($e->getFile(), $e->getLine()) .
                        self::parseTrace($e->getTrace()) .
                    '</div>
                </div>';
        }

        /**
         * Highlights a single line from a PHP file.
         * @param string $file File path.
         * @param int $line Line to highlight.
         * @return string Highlighted result in HTML.
         */
        private static function highlight(string $file, int $line){
            if(!is_readable($file)) return '';
            $content = file_get_contents($file);
            if($content === false) return '';
            $content = str_replace(["\r\n", "\r"], "\n", $content);
            $content = explode("\n", highlight_string($content, true));
            $content = str_replace('<br />', "\n", $content[1]);
            $content = explode("\n", str_replace("\r\n", "\n", $content));
            if(empty($content[$line - 1])) return '';
            return '<code style="white-space: wrap; word-wrap: break-word; font-size: 16px; display: block; border: 1px solid gainsboro; margin: 20px 0; background-color: #f5f5f5; padding: 15px;">
                        <span style="color: #75715E;">' . $line . '</span>' . $content[$line - 1] . '</span>
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
                        <table cellspacing="0" cellpadding="0" style="width: 100%; margin-top: 10px;"><tbody>';
            foreach(array_reverse($trace) as $key => $item){
                if($item['class'] == 'Glowie\Core\Error') continue;
                $isTraceable = true;
                $result .=   '<tr>
                                <td style="border: 1px solid lightgray; padding: 10px; vertical-align: top; text-align: center; font-weight: bold; color: #ed578b; width: 40px;">#' . ($key + 1) .'</td>
                                <td style="border: 1px solid lightgray; padding: 10px;"> 
                                    <i style="color: dimgray; display: block; font-size: 14px;">' . $item['file'] . ':' . $item['line'] . '</i>
                                    <span style="color: #d2024a; font-weight: 600;">'. $item['class'] . '</span>-><span style="color: #ed578b">' . $item['function'] . '()</span>
                                    ' . (!empty($item['args']) ? '<i style="font-size: 14px; font-weight: 600; display: block; margin: 10px 0;">Args:</i><pre style="white-space: wrap; word-wrap: break-word; background-color: #f5f5f5; border: 1px solid gainsboro; padding: 15px; margin: 0;">' . print_r($item['args'], true) . '</pre>' : '') . '
                                </td>
                            </tr>';
            }
            $result .= '</tbody></table>';
            return $isTraceable ? $result : '';
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