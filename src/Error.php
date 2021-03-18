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
         * Instantiates a new error handler.
         */
        public function __construct(){
            // Registers error handling functions
            error_reporting($GLOBALS['glowieConfig']['error_reporting']);
            set_exception_handler([$this, 'exceptionHandler']);
            register_shutdown_function([$this, 'fatalHandler']);
            set_error_handler([$this, 'errorHandler']);
            ini_set('display_errors', 'Off');
            ini_set('display_startup_errors', 'Off');

            // Sets syntax highliter style
            ini_set('highlight.comment', '#75715E');
            ini_set('highlight.default', '#FFFFFF');
            ini_set('highlight.html', '#06B');
            ini_set('highlight.keyword', '#F92672');
            ini_set('highlight.string', '#E6DB74');
        }

        /**
         * Default error handler. Throws an exception based on given error.
         * @param int $level Error level code.
         * @param string $str Error message.
         * @param string $file (Optional) Filename where the error was thrown.
         * @param int $line (Optional) Line number where the error was thrown.
         */
        public function errorHandler(int $level, string $str, string $file = '', int $line = 0){
            if(error_reporting() & $level){
                $this->exceptionHandler(new ErrorException($str, 0, $level, $file, $line));
                exit();
            }
        }

        /**
         * Fatal error handler.
         */
        public function fatalHandler(){
            $error = error_get_last();
            if ($error && $error["type"] == E_ERROR) $this->errorHandler($error["type"], $error["message"], $error["file"], $error["line"]);
            exit();
        }

        /**
         * Exception handler.
         * @param Exception $e Thrown exception.
         */
        public function exceptionHandler($e){
            echo '<style>
                    .glowieError{
                        font-family: Segoe UI, sans-serif;
                        font-size: 18px;
                        background-color: white;
                        color: black;
                        margin: 10px;
                        padding: 20px;
                        border: 1px solid gainsboro;
                        box-shadow: 2px 3px 2px gainsboro;
                    }

                    .glowieError strong{
                        color: #ec1c64;
                    }

                    .glowieError i{
                        color: dimgray;
                        display: block;
                    }

                    .glowieError pre{
                        font-size: 18px;
                        margin: 0;
                        color: #ed578b;
                    }

                    .glowieError code{
                        display: block;
                        margin: 20px 0;
                        background-color: #2e2e2e;
                        padding: 15px;
                    }

                    .glowieError .lineNumber{
                        color: #75715E;
                    }

                    .glowieError .time{
                        font-size: 14px;
                        color: gray;
                        display: block;
                        margin-top: 20px;
                    }
                </style>
                <div class="glowieError">
                    <strong>Application error:</strong> ' . $e->getMessage() . '<br>
                    <i>' . $e->getFile() . ':' . $e->getLine() . '</i>' . 
                    $this->highlight($e->getFile(), $e->getLine()) . '
                    <strong>Stack trace:</strong>
                    <pre>' . $e->getTraceAsString() . '</pre>
                    <span class="time">Exception thrown in ' . $this->getExceptionTime() . ' seconds.</span>
                </div>';
        }

        /**
         * Highlights a single line from a PHP file.
         * @param string $file File path.
         * @param int $line Line to highlight.
         * @return string Highlighted result in HTML.
         */
        private function highlight(string $file, int $line){
            if(!is_readable($file)) return '';
            $content = file_get_contents($file);
            $content = str_replace(["\r\n", "\r"], "\n", $content);
            $content = explode("\n", highlight_string($content, true));
            $content = str_replace('<br />', "\n", $content[1]);
            $content = explode("\n", str_replace("\r\n", "\n", $content));
            if(!$file[$line - 1]) return '';
            return '<code><span class="lineNumber">' . $line . '</span>' . $content[$line - 1] . '</span></code>';
        }

        /**
         * Returns the page exception time.
         * @return float Exception time.
         */
        private function getExceptionTime(){
            return round((microtime(true) - $GLOBALS['glowieTimer']), 5);
        }

    }

?>