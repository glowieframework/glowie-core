<?php
    namespace Glowie\Core\Error;

    use Config;
    use Util;
    use Glowie\Core\Http\Response;
    use Glowie\Core\View\Buffer;
    use ErrorException;

    /**
     * Error handler for Glowie application.
     * @category Error handler
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.1
     */
    class Handler{

        /**
         * Registers the error handlers and INI settings.
         */
        public static function register(){
            // Registers error handling functions
            $level = Config::get('error_reporting.level', E_ALL);
            error_reporting($level);
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            set_exception_handler([self::class, 'exceptionHandler']);
            set_error_handler([self::class, 'errorHandler'], $level);
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');

            // INI settings
            ini_set('highlight.comment', '#8BC34A');
            ini_set('highlight.default', '#545454');
            ini_set('highlight.html', '#06B');
            ini_set('highlight.keyword', '#FF5722');
            ini_set('highlight.string', '#4CAF50');
        }

        /**
         * Default error handler. Throws an exception based in a given error.
         * @param int $level Error level.
         * @param string $message Error message.
         * @param string|null $file (Optional) Filename where the error occurred.
         * @param int|null $line (Optional) Line number where the error was triggered.
         */
        public static function errorHandler(int $level, string $message, ?string $file = '', ?int $line = 0){
            throw new ErrorException($message, 0, $level, $file, $line);
            return true;
        }

        /**
         * Exception handler.
         * @param Exception $e Thrown exception.
         */
        public static function exceptionHandler($e){
            // Error logging
            $date = date('Y-m-d H:i:s');
            self::log("[{$date}] {$e->getMessage()} at file {$e->getFile()}:{$e->getLine()}\n{$e->getTraceAsString()}\n\n");

            // Clean output buffer
            Buffer::clean();

            // Display the error or the default error page
            http_response_code(Response::HTTP_INTERNAL_SERVER_ERROR);
            if(error_reporting()){
                include(__DIR__ . '/Views/error.phtml');
            }else{
                include(__DIR__ . '/Views/default.phtml');
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
            return '<span style="color: #75715E;">' . $line . '</span> ' . $text . '</span>';
        }

        /**
         * Parses the stack trace to a table.
         * @param array $trace Stack trace array.
         * @return string Table result in HTML.
         */
        protected static function parseTrace(array $trace){
            $isTraceable = false;
            $result =    '<strong style="color: #ed578b;">Stack trace:</strong>
                        <table cellspacing="0" cellpadding="0"><tbody>';
            foreach($trace as $key => $item){
                if(!empty($item['class']) && $item['class'] == self::class) continue;
                if(!$isTraceable) $isTraceable = true;
                $result .=   '<tr>' .
                                // Index
                                '<th>#' . ($key + 1) .'</th>' .
                                '<td>' .
                                    // File/line
                                    (!empty($item['file']) && !empty($item['line']) ? '<i>' . $item['file'] . ':' . $item['line'] . '</i>' : '') .

                                    // Class
                                    (!empty($item['class']) ? '<span class="class">' . $item['class'] . '</span>-><span class="method">' . $item['function'] . '()</span>' : '') .

                                    // Highlight
                                    (!empty($item['file']) && !empty($item['line']) ? '<code>' . self::highlight($item['file'], $item['line']) . '</code>' : '') .

                                    // Args
                                    (!empty($item['args']) ? '<a href="javascript:void(0);" onclick="javascript:toggleArgs(\'#args_' . $key . '\')" class="args-toggle">View args</a><pre class="args" id="args_' . $key . '">' . self::getDump($item['args']) . '</pre>' : '') . '
                                </td>
                            </tr>';
            }
            $result .= '</tbody></table>';
            return $isTraceable ? $result : '';
        }

        /**
         * Returns the value of `var_dump()` method to a string.
         * @param mixed $var Variable to dump.
         * @return string The variable dump as string.
         */
        private static function getDump($var){
            Buffer::start();
            var_dump($var);
            return Buffer::get();
        }

        /**
         * Logs the error to the error.log file.
         * @param string $content Content to append to the file.
         */
        private static function log(string $content){
            if(!Config::get('error_reporting.logging', true)) return;
            file_put_contents(Config::get('error_reporting.file', Util::location('storage/error.log')), $content, FILE_APPEND);
        }

        /**
         * Returns the page exception time.
         * @return float Exception time.
         */
        protected static function getExceptionTime(){
            return round((microtime(true) - APP_START_TIME) * 1000, 2) . 'ms';
        }

    }

?>