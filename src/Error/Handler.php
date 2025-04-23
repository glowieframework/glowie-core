<?php

namespace Glowie\Core\Error;

use Config;
use Util;
use Glowie\Core\Http\Rails;
use Glowie\Core\View\Buffer;
use Glowie\Core\Http\Response;
use ErrorException;

/**
 * Error handler for Glowie application.
 * @category Error handler
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 */
class Handler
{

    /**
     * Start line number from context.
     * @var int
     */
    protected static $startLine = 1;

    /**
     * Registers the error handlers and INI settings.
     */
    public static function register()
    {
        // Registers error handling functions
        $level = Config::get('error_reporting.level', E_ALL);
        error_reporting($level);
        set_exception_handler([self::class, 'exceptionHandler']);
        set_error_handler([self::class, 'errorHandler'], $level);

        // INI settings
        ini_set('display_errors', '1');
        ini_set('display_startup_errors', '1');
    }

    /**
     * Default error handler. Throws an exception based in a given error.
     * @param int $level Error level.
     * @param string $message Error message.
     * @param string|null $file (Optional) Filename where the error occurred.
     * @param int|null $line (Optional) Line number where the error was triggered.
     */
    public static function errorHandler(int $level, string $message, ?string $file = null, ?int $line = 0)
    {
        throw new ErrorException($message, 0, $level, $file ?? '', $line);
        return true;
    }

    /**
     * Exception handler.
     * @param Exception $e Thrown exception.
     */
    public static function exceptionHandler($e)
    {
        // Error logging
        $date = date('Y-m-d H:i:s');
        self::log("[{$date}] {$e->getMessage()} at file {$e->getFile()}:{$e->getLine()}\n{$e->getTraceAsString()}\n\n");

        // Clean output buffer
        if (Buffer::isActive()) Buffer::clean();

        // Display the error or the default error page
        http_response_code(Response::HTTP_INTERNAL_SERVER_ERROR);
        if (error_reporting()) {
            include(__DIR__ . '/Views/error.phtml');
        } else {
            extract([
                'title' => 'Server Error',
                'text' => '500 | Server Error'
            ]);
            include(__DIR__ . '/Views/default.phtml');
        }

        // Exit with error status code
        exit(500);
    }

    /**
     * Highlights a single line from a PHP file.
     * @param string $file File path.
     * @param int $line Line to highlight.
     * @param bool $context (Optional) Include previous and next lines.
     * @return string Highlighted result in HTML.
     */
    private static function highlight(string $file, int $line, bool $context = false)
    {
        try {
            // Checks for the file
            if (!is_readable($file)) return '';
            $text = @file($file);
            if ($text === false || !isset($text[$line - 1])) return '';

            // Single line print only
            if (!$context || count($text) < 2) {
                self::$startLine = $line;
                return trim($text[$line - 1]);
            }

            // Parses the context lines
            $start = max(1, $line - 5);
            $end = min(count($text), $line + 5);
            self::$startLine = $start;

            // Parses the code
            $result = '';
            for ($i = $start; $i <= $end; $i++) $result .= ' ' . $text[$i - 1];

            // Returns resulting block
            return $result;
        } catch (\Throwable $th) {
            return '';
        }
    }

    /**
     * Parses the stack trace to a table.
     * @param array $trace Stack trace array.
     * @return string Table result in HTML.
     */
    protected static function parseTrace(array $trace)
    {
        // Prepare result
        $result =   '<strong class="stack-title">Stack trace:</strong>';
        $result .=  '<a href="" class="args-toggle vendor-toggle">Toggle vendor <span class="icon arrow-down"></span></a>';
        $result .=  '<table cellspacing="0" cellpadding="0"><tbody>';

        // Iterate through stack trace
        foreach ($trace as $key => $item) {
            // Identify vendor classes
            $vendor = false;
            if (!empty($item['class']) && $item['class'] == self::class) continue;
            if (!empty($item['class']) && Util::startsWith($item['class'], 'Glowie\Core')) $vendor = true;

            // Add result to the HTML table
            $result .=  '<tr class="' . ($vendor ? 'vendor hide' : '') . '">';
            $result .=  '<th>#' . (count($trace) - $key) . '</th>';
            $result .=  '<td>';

            // File/line
            if (!empty($item['file']) && !empty($item['line'])) {
                $result .= '<i>' . $item['file'] . ':' . $item['line'] . '</i>';
            }

            // Class
            if (!empty($item['class'])) {
                $result .= '<span class="class">' . $item['class'] . '</span>::<span class="method">' . $item['function'] . '()</span>';
            }

            // Highlight
            if (!empty($item['file']) && !empty($item['line'])) {
                $result .= '<pre><code data-ln-start-from="' . $item['line'] . '" class="language-php">' . self::highlight($item['file'], $item['line']) . '</code></pre>';
            }

            // Args
            if (!empty($item['args'])) {
                $result .= '<a href="" class="args-toggle">View args <span class="icon arrow-down"></span></a><pre class="args">' . self::getDump($item['args']) . '</pre>';
            }

            // Closing column
            $result .= '</td></tr>';
        }

        // Close result table
        $result .= '</tbody></table>';

        // Return result
        return !empty($trace) ? $result : '';
    }

    /**
     * Parses the request body to a table.
     * @return string Table content as HTML.
     */
    protected static function parseRequest()
    {
        try {
            $data = Rails::getRequest()->toCollection()->sortKeys();
            if (!empty($data->toArray())) return '<strong class="stack-title">Request Body</strong>' . self::tableVars($data);
            return '';
        } catch (\Throwable $th) {
            return '';
        }
    }

    /**
     * Parses the request headers to a table.
     * @return string Table content as HTML.
     */
    protected static function parseRequestHeaders()
    {
        try {
            $data = Rails::getRequest()->getHeaders()->sortKeys();
            if (!empty($data->toArray())) return '<strong class="stack-title">Request Headers</strong>' . self::tableVars($data);
            return '';
        } catch (\Throwable $th) {
            return '';
        }
    }

    /**
     * Parses the response headers to a table.
     * @return string Table content as HTML.
     */
    protected static function parseResponseHeaders()
    {
        try {
            $data = Rails::getResponse()->getHeaders()->sortKeys();
            if (!empty($data->toArray())) return '<strong class="stack-title">Response Headers</strong>' . self::tableVars($data);
            return '';
        } catch (\Throwable $th) {
            return '';
        }
    }

    /**
     * Parses the route parameters to a table.
     * @return string Table content as HTML.
     */
    protected static function parseRoute()
    {
        try {
            $data = Rails::getParams()->toArray();
            if (!empty($data)) return '<strong class="stack-title">Route Parameters</strong>' . self::tableVars($data);
            return '';
        } catch (\Throwable $th) {
            return '';
        }
    }

    /**
     * Parses an associative array to a table.
     * @param array|Collection $vars Vars to be parsed.
     * @return string Table content as HTML.
     */
    private static function tableVars($vars)
    {
        $result = '<table cellpadding="0" cellspacing="0"><tbody>';

        foreach ($vars as $key => $value) {
            $result .= '<tr>';
            $result .= '<th class="auto">' . (string)$key . '</th>';
            $result .= '<td><pre>' . (string)$value . '</pre></td>';
            $result .= '</tr>';
        }

        $result .= '</tbody></table>';
        return $result;
    }

    /**
     * Returns the value of `var_dump()` method to a string.
     * @param mixed $var Variable to dump.
     * @return string The variable dump as string.
     */
    private static function getDump($var)
    {
        Buffer::start();
        var_dump($var);
        return Buffer::get();
    }

    /**
     * Logs the error to the error.log file.
     * @param string $content Content to append to the file.
     */
    public static function log(string $content)
    {
        if (!Config::get('error_reporting.logging', true)) return;
        $file = Config::get('error_reporting.file', Util::location('storage/error.log'));
        if (!is_writable(dirname($file))) return;
        @file_put_contents($file, $content, FILE_APPEND);
    }

    /**
     * Returns the page exception time.
     * @return float Exception time.
     */
    protected static function getExceptionTime()
    {
        return round((microtime(true) - APP_START_TIME) * 1000, 2) . 'ms';
    }

    /**
     * Gets the content and minifies an asset.
     * @param string $filename Asset relative filename.
     * @return string Returns the minified file content.
     */
    public static function getAsset(string $filename)
    {
        $type = pathinfo($filename, PATHINFO_EXTENSION);
        $content = file_get_contents(__DIR__ . '/Views/assets/' . $filename);
        if ($type == 'css') $content = str_replace([': ', ' {', ', '], [':', '{', ','], $content);
        if ($type == 'js') $content = preg_replace("/\/\*[\s\S]*?\*\//", '', $content);
        return str_replace(["\r", "\n", "\t"], '', $content);
    }
}
