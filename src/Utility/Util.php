<?php

use Glowie\Core\CLI\Firefly;
use Glowie\Core\Http\Rails;
use Glowie\Core\Http\Session;
use Glowie\Core\Http\Response;
use Glowie\Core\View\Buffer;
use Glowie\Core\Exception\FileException;
use Glowie\Core\Element;

/**
 * Miscellaneous utilities for Glowie application.
 * @category Utility
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 * @see https://glowie.gabrielsilva.dev.br/docs/latest/extra/util
 */
class Util
{

    /**
     * Returns the current Glowie core version.
     * @return string Current Glowie core version.
     */
    public static function getVersion()
    {
        $file = __DIR__ . '/../../version.txt';
        return file_get_contents($file);
    }

    /**
     * Dumps a variable in a human-readable way and ends the script execution.
     * @param mixed $var Variable to be dumped.
     * @param bool $plain (Optional) Dump variable as plain text instead of HTML.
     * @param bool $continue (Optional) Continue script execution after dump.
     */
    public static function dump($var, bool $plain = false, bool $continue = false)
    {
        // Dumps the content
        if (self::isCLI()) {
            Firefly::print(self::parseDump($var, true), false);
            Firefly::print('</color>');
        } else {
            // Clean output buffer
            if (Buffer::isActive()) Buffer::clean();

            // Sets response code
            Rails::getResponse()->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);

            // Checks if app debug is enabled
            if (error_reporting()) {
                if ($plain) {
                    Rails::getResponse()->setContentType(Response::CONTENT_PLAIN);
                    var_dump($var);
                } else {
                    Rails::getResponse()->setContentType(Response::CONTENT_HTML);
                    include(__DIR__ . '/Views/dump.phtml');
                }
            } else {
                extract([
                    'title' => 'Server Error',
                    'text' => '500 | Server Error'
                ]);
                include(__DIR__ . '/../Error/Views/default.phtml');
            }
        }

        // Stop script
        if (!$continue) die();
    }

    /**
     * Parses a variable in an HTML structure recursively.
     * @param mixed $var Variable to parse.
     * @param bool $cli (Optional) Parse in CLI mode.
     * @param int $space (Optional) CLI tab spacement for current iteration.
     * @return string Returns the HTML/CLI dump code.
     */
    protected static function parseDump($var, bool $cli = false, int $space = 2)
    {
        // Prepares the result string
        $html = '';

        // Checks if variable is an object, array or resource
        $class = null;
        if (is_object($var)) {
            $class = get_class($var);
            $html .= $cli ? '<color="yellow">' . $class : '<a href="" class="toggle">{' . $class;

            // Checks for Closure variable
            if ($var instanceof Closure) {
                $reflection = new ReflectionFunction($var);
                $var = [
                    'class' => $reflection->getClosureScopeClass()->name,
                    'this' => get_class($reflection->getClosureThis()),
                    'file' => $reflection->getFileName(),
                    'line' => $reflection->getStartLine() . ' to ' . $reflection->getEndLine()
                ];
            } else {
                // Cast Element or object to array
                $var = is_callable([$var, 'toArray']) ? $var->toArray() : (array)$var;
            }

            // Counts the properties
            if ($cli) {
                $html .= '(' . count($var) . '){</color>';
            } else {
                $html .= '(' . count($var) . ')<span class="icon arrow-down"></span>}</a>';
            }
        } else if (is_array($var)) {
            if ($cli) {
                $wasArray = true;
                $html .= '<color="magenta">array(' . count($var) . ')[</color>';
            } else {
                $html .= '<a href="" class="toggle array">[array(' . count($var) . ')<span class="icon arrow-down"></span>]</a>';
            }
        } else if (is_resource($var)) {
            $html .= $cli ? '<color="cyan">{' . get_resource_type($var) : '<a href="" class="toggle">{' . get_resource_type($var);
            $var = stream_get_meta_data($var);
            if ($cli) {
                $html .= '(' . count($var) . ')</color>';
            } else {
                $html .= '(' . count($var) . ')<span class="icon arrow-down"></span>}</a>';
            }
        }

        // Checks for variable type
        if (is_array($var)) {
            $html .= $cli ? '' : '<div class="collapse">';

            // Gets each array key/value pair
            foreach ($var as $key => $value) {
                $html .= $cli ? PHP_EOL : '<div>';

                // Replace class visibility identifiers
                if ($class) {
                    if (Util::startsWith($key, "\0" . $class . "\0")) $key = Util::replaceFirst($key, "\0" . $class . "\0", '');
                    if (Util::startsWith($key, "\0*\0")) $key = Util::replaceFirst($key, "\0*\0", '');
                }

                // Put variable value recursively
                if ($cli) {
                    if (is_string($key)) {
                        $html .= str_repeat(' ', $space) . '<color="green">"' . $key . '" </color>=> ';
                    } else {
                        $html .= str_repeat(' ', $space) . '<color="blue">' . $key . ' </color>=> ';
                    }
                } else {
                    if (is_string($key)) {
                        $html .= '<span class="string"><strong>"' . htmlspecialchars($key) . '"</strong></span> => ';
                    } else {
                        $html .= '<span class="other"><strong>' . htmlspecialchars($key) . '</strong></span> => ';
                    }
                }

                $html .= self::parseDump($value, $cli, $space + 2);
                $html .= $cli ? '' : '</div>';
            }

            if ($cli) {
                if (!empty($wasArray)) {
                    $html .= PHP_EOL . str_repeat(' ', $space - 2) . '<color="magenta">]</color>';
                } else {
                    $html .= PHP_EOL . str_repeat(' ', $space - 2) . '<color="yellow">}</color>';
                }
            } else {
                $html .= '</div>';
            }
        } else if (is_string($var)) {
            if ($cli) {
                $html .= '<color="green">';
            } else {
                $html .= '<span class="string" title="' . mb_strlen($var) . ' characters">';
            }
            $html .= '"' . self::limitString($cli ? $var : htmlspecialchars($var), 3000) . '"';
            $html .= $cli ? '</color>' : '</span>';
        } else if (is_null($var)) {
            $html .= $cli ? '<color="blue">null</color>' : '<span class="other">null</span>';
        } else if (is_bool($var)) {
            $html .= $cli ? '<color="blue">' : '<span class="other">';
            $html .= $var ? 'true' : 'false';
            $html .= $cli ? '</color>' : '</span>';
        } else {
            $html .= $cli ? '<color="blue">' : '<span class="other">';
            $html .= $cli ? (string)$var : htmlspecialchars((string)$var);
            $html .= $cli ? '</color>' : '</span>';
        }

        // Returns the result
        return $html;
    }

    /**
     * Tries to run a function until the number of attempts is reached.
     * @param int $attempts Maximum number of attempts.
     * @param Closure $callback Function to be called.
     * @param int $sleep (Optional) Delay between each try (in milliseconds).
     * @return mixed Returns the function result on success.
     */
    public static function retry(int $attempts, Closure $callback, int $sleep = 100)
    {
        try {
            return call_user_func($callback);
        } catch (\Throwable $th) {
            if ($attempts == 1) throw $th;
            usleep($sleep * 1000);
            return self::retry($attempts - 1, $callback, $sleep);
        }
    }

    /**
     * Returns the absolute URL of the application path.
     * @param string $path (Optional) Relative path to append to the base URL.
     * @return string Full base URL.
     */
    public static function baseUrl(string $path = '')
    {
        return trim(APP_BASE_URL, '/') . '/' . trim($path, '/');
    }

    /**
     * Returns the absolute URL of the application path for a custom domain.
     * @param string $domain Domain name.
     * @param string $path (Optional) Relative path to append to the base URL.
     * @return string Full base URL.
     */
    public static function baseUrlDomain(string $domain, string $path = '')
    {
        $domain = mb_strtolower($domain);
        $http = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
        $baseUrl = !self::startsWith($domain, ['http://', 'https://']) ? $http : '';
        $baseUrl .= trim($domain, '/') . '/' . APP_FOLDER;
        return trim($baseUrl, '/') . '/' . trim($path, '/');
    }

    /**
     * Returns the real application location in the file system.
     * @param string $path (Optional) Relative path to append to the location.
     * @return string Full location.
     */
    public static function location(string $path = '')
    {
        return APP_LOCATION . rtrim($path, '/');
    }

    /**
     * Returns the base URL of a named route.
     * @param string $route Route name.
     * @param array $params (Optional) Route parameters to bind into the URL.
     * @return string Returns the absolute URL of the application path with the route appended.
     */
    public static function route(string $route, array $params = [])
    {
        // Gets the named route
        $routeData = Rails::getRoute($route);
        if (empty($routeData)) throw new Exception('route(): Route name "' . $route . '" does not match any existing route');

        // Gets the route parameters
        $result = [];
        $missing = [];
        if (preg_match_all('~:(\w+)~', $routeData['uri'], $segments) && !empty($segments[1])) {
            $uri = $routeData['uri'];
            foreach ($segments[1] as $item) {
                if (isset($params[$item])) {
                    $result[$item] = $params[$item];
                    $uri = preg_replace('~:' . preg_quote($item) . '~i', $params[$item], $uri);
                } else {
                    $missing[] = $item;
                }
            }
        }

        // Validates missing parameters
        if (!empty($missing)) throw new Exception('route(): Missing parameter(s) "' . implode('", "', $missing) . '" for route "' . $route . '"');

        // Checks if the route has any parameters
        if (!empty($result)) {
            $remaining = array_diff_key($params, $result);
            $url = $uri . (!empty($remaining) ? '?' . http_build_query($remaining) : '');
        } else {
            $url = $routeData['uri'] . (!empty($params) ? '?' . http_build_query($params) : '');
        }

        // Checks if the route has a specific domain
        if (!is_null($routeData['domain'])) {
            return self::baseUrlDomain($routeData['domain'], $url);
        } else {
            return self::baseUrl($url);
        }
    }

    /**
     * Checks if the current route name matches a string.
     * @param string $route The string to match the route name.
     * @return bool Returns true if route matches, false otherwise.
     */
    public static function isCurrentRoute(string $route)
    {
        return Rails::getCurrentRoute() === $route;
    }

    /**
     * Checks if the current route group matches a string.
     * @param string $group The string to match the routes group name.
     * @return bool Returns true if group matches, false otherwise.
     */
    public static function isCurrentGroup(string $group)
    {
        return Rails::getCurrentGroup() === $group;
    }

    /**
     * Returns the base URL of an asset file with a token to force cache reloading on browsers.
     * @param string $filename Asset filename. Must be a path relative to the **app/public/assets** folder.
     * @param string $token (Optional) Token parameter name to append to the filename.
     * @return string Returns the absolute URL of the asset file with the token.
     */
    public static function asset(string $filename, string $token = 'assetVersion')
    {
        $filename = 'assets/' . trim($filename, '/');
        if (!is_file($filename)) throw new FileException('Asset file "app/public/' . $filename . '" was not found');
        return self::baseUrl(sprintf('%s?%s=%s', $filename, $token, md5(filemtime($filename))));
    }

    /**
     * Reorders a multi-dimensional array by a key value.
     * @param array $array Array to reorder.
     * @param mixed $key Key to use as the reordering base.
     * @param int $order (Optional) Ordering direction: `SORT_ASC` (ascending) or `SORT_DESC` (descending).
     * @return array Returns the resulting array.
     */
    public static function orderArray(array $array, $key, int $order = SORT_ASC)
    {
        if (!empty($array)) {
            foreach ($array as $col => $row) {
                $data[$col] = $row[$key] ?? '';
            }
            if (!empty($data)) array_multisort($data, $order, $array);
        }
        return $array;
    }

    /**
     * Filters a multi-dimensional array leaving only items that match a key value.
     * @param array $array Array to filter.
     * @param mixed $key Key to use as the filtering base. You can also use an associative array with keys and values to match.
     * @param mixed $value (Optional) Value to filter if using a single key.
     * @param bool $strict (Optional) Use strict types while comparing values.
     * @return array Returns the resulting array.
     */
    public static function filterArray(array $array, $key, $value = null, bool $strict = false)
    {
        $result = [];
        if (!empty($array)) {
            if (is_array($key)) {
                $result = array_filter($array, function ($row) use ($key, $strict) {
                    $filterReturn = true;
                    foreach ($key as $filterKey => $filterValue) {
                        if (!array_key_exists($filterKey, $row) || $row[$filterKey] != $filterValue || ($strict && $row[$filterKey] !== $filterValue)) {
                            $filterReturn = false;
                        };
                        if (!$filterReturn) break;
                    }
                    return $filterReturn;
                });
            } else {
                $result = array_filter($array, function ($row) use ($key, $value, $strict) {
                    if (!array_key_exists($key, $row)) return false;
                    if ($strict) return $row[$key] === $value;
                    return $row[$key] == $value;
                });
            }
        }
        return $result;
    }

    /**
     * Searches a multi-dimensional array for the first item that matches a key value.
     * @param array $array Array to search.
     * @param mixed $key Key to match value.
     * @param mixed $value Value to search.
     * @return array Returns the first array item found.
     */
    public static function searchArray(array $array, $key, $value)
    {
        $result = [];
        if (!empty($array)) {
            $index = array_search($value, array_column($array, $key));
            if ($index !== false) $result = $array[$index];
        }
        return $result;
    }

    /**
     * Flattens a multi-dimensional array into a single level array with dot notation.
     * @param array $array Array to flatten.
     * @return array Returns the resulting array.
     */
    public static function dotArray(array $array)
    {
        $result = [];
        if (!empty($array)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($array));
            foreach ($iterator as $item) {
                $keys = [];
                foreach (range(0, $iterator->getDepth()) as $depth) {
                    $keys[] = $iterator->getSubIterator($depth)->key();
                }
                $key = implode('.', $keys);
                $result[$key] = $item;
            }
        }
        return $result;
    }

    /**
     * Unflattens a single level array in dot notation to a multi-dimensional array.
     * @param array $array Array to unflatten.
     * @return array Returns the resulting array.
     */
    public static function undotArray(array $array)
    {
        $result = [];
        foreach ($array as $key => $value) {
            $parts = explode('.', $key);
            $nested = &$result;
            while (count($parts) > 1) {
                $nested = &$nested[array_shift($parts)];
                if (!is_array($nested)) $nested = [];
            }
            $nested[array_shift($parts)] = $value;
        }
        return $result;
    }

    /**
     * Checks if an array is associative rather than numerically indexed.
     * @param mixed $array Array to check.
     * @return bool Returns true if is an associative array.
     */
    public static function isAssociativeArray($array)
    {
        if (!is_array($array) || empty($array)) return false;
        $keys = array_keys($array);
        return array_keys($keys) !== $keys;
    }

    /**
     * Checks if an array is multidimensional.
     * @param mixed $array Array to check.
     * @return bool Returns true if is a multidimensional array.
     */
    public static function isMultidimensionalArray($array)
    {
        if (!is_array($array) || empty($array)) return false;
        foreach ($array as $value) {
            if (is_array($value)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns a random item from an array.
     * @param array $array Array to pick a random item.
     * @return mixed Returns the random item value.
     */
    public static function randomArray(array $array)
    {
        return $array[array_rand($array)];
    }

    /**
     * Returns a paginated version of an array.
     * @param array $array Array to be paginated.
     * @param int $currentPage (Optional) Current page to get results.
     * @param int $resultsPerPage (Optional) Number of results to get per page.
     * @param int|null $range (Optional) Pagination range interval (for `pages` array).
     * @return Element Returns an Element with the pagination result.
     */
    public static function paginateArray(array $array, int $currentPage = 1, int $resultsPerPage = 25, ?int $range = null)
    {
        // Counts total pages
        $totalResults = count($array);
        $totalPages = floor($totalResults / $resultsPerPage);
        if ($totalResults % $resultsPerPage != 0) $totalPages++;

        // Gets paginated results
        $offset = ($currentPage - 1) * $resultsPerPage;
        $results = array_slice($array, $offset, $resultsPerPage);

        // Create pages array
        $pages = [];

        // Check for ranged pagination
        if (!empty($range)) {
            $rangeHalf = floor($range / 2);
            $rangeStart = max(1, $currentPage - $rangeHalf);
            $rangeEnd = min($totalPages, $currentPage + $rangeHalf);
            $rangeStart = max(1, min($rangeStart, $totalPages - $range + 1));
            $rangeEnd = min($totalPages, max($rangeEnd, $range));

            for ($i = $rangeStart; $i <= $rangeEnd; $i++) {
                $pages[] = new Element([
                    'label' => $i,
                    'active' => $currentPage == $i
                ]);
            }
        } else {
            for ($i = 1; $i <= $totalPages; $i++) {
                $pages[] = new Element([
                    'label' => $i,
                    'active' => $currentPage == $i
                ]);
            }
        }

        // Parse results
        return new Element([
            'page' => $currentPage,
            'is_valid' => !empty($results),
            'data' => $results,
            'pages' => $pages,
            'from' => empty($results) ? 0 : $offset + 1,
            'to' => empty($results) ? 0 : count($results) + $offset,
            'total_pages' => (int)$totalPages,
            'previous_page' => $currentPage == 1 ? null : $currentPage - 1,
            'next_page' => $currentPage == $totalPages ? null : $currentPage + 1,
            'results_per_page' => $resultsPerPage,
            'total_results' => $totalResults,
            'range' => $range
        ]);
    }

    /**
     * Returns a value from a multi-dimensional array in dot notation.
     * @param array $array Array to get the value.
     * @param mixed $key Key to get in dot notation.
     * @param mixed $default (Optional) Default value to return if the key does not exist.
     * @return mixed Returns the value if exists or the default if not.
     */
    public static function arrayGet(array $array, $key, $default = null)
    {
        // Checks if the key does not exist already
        if (array_key_exists($key, $array)) return $array[$key];

        // Loops through each key
        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) return $default;
            $array = $array[$segment];
        }

        // Returns the value
        return $array;
    }

    /**
     * Sets a value to a key in a multi-dimensional array using dot notation.
     * @param array $array Array to set the value.
     * @param mixed $key Key to set in dot notation.
     * @param mixed $value Value to set.
     */
    public static function arraySet(array &$array, $key, $value)
    {
        $item = &$array;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($item) || !array_key_exists($segment, $item)) $item[$segment] = [];
            $item = &$item[$segment];
        }
        $item = $value;
    }

    /**
     * Deletes a key from a multi-dimensional array using dot notation.
     * @param array $array Array to delete the value.
     * @param mixed $key Key to delete in dot notation.
     */
    public static function arrayDelete(array &$array, $key)
    {
        $keys = explode('.', $key);
        $lastKey = array_pop($keys);
        $item = &$array;
        foreach ($keys as $segment) {
            if (!is_array($item) || !array_key_exists($segment, $item)) return;
            $item = &$item[$segment];
        }
        unset($item[$lastKey]);
    }

    /**
     * Returns a value from a multi-dimensional object in dot notation.
     * @param object $object Object to get the value.
     * @param string $key Key to get in dot notation.
     * @param mixed $default (Optional) Default value to return if the key does not exist.
     * @return mixed Returns the value if exists or the default if not.
     */
    public static function objectGet(object $object, string $key, $default = null)
    {
        // Checks if the key does not exist already
        if (isset($object->{$key})) return $object->{$key};

        // Loops through each property
        foreach (explode('.', $key) as $segment) {
            if (!is_object($object) || !isset($object->{$segment})) return $default;
            $object = $object->{$segment};
        }

        // Returns the value
        return $object;
    }

    /**
     * Returns a CSS classlist string based on giving conditions.
     * @param array $array Array of conditions. The key must be the class name, and the value a boolean expression.\
     * If you omit the key, the class will be added anyway.
     * @return string Returns the classlist.
     */
    public static function cssArray(array $array)
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_numeric($key)) {
                $result[] = $value;
            } else if ($value) {
                $result[] = $key;
            }
        }
        return implode(' ', $result);
    }

    /**
     * Checks if a string starts with a given substring.
     * @param string $haystack The string to search in.
     * @param string|array $needle The substring to search for in the haystack. You can also use an array of strings.
     * @return bool Returns **true** if haystack begins with needle, **false** otherwise.
     */
    public static function startsWith(string $haystack, $needle)
    {
        $result = false;
        foreach ((array)$needle as $item) {
            if ($result) break;
            $length = mb_strlen($item);
            $result = mb_substr($haystack, 0, $length) == $item;
        }
        return $result;
    }

    /**
     * Checks if a string ends with a given substring.
     * @param string $haystack The string to search in.
     * @param string|array $needle The substring to search for in the haystack. You can also use an array of strings.
     * @return bool Returns **true** if haystack ends with needle, **false** otherwise.
     */
    public static function endsWith(string $haystack, $needle)
    {
        $result = false;
        foreach ((array)$needle as $item) {
            if ($result) break;
            $length = mb_strlen($item);
            if (!$length) {
                $result = true;
            } else {
                $result = mb_substr($haystack, -$length) == $item;
            }
        }
        return $result;
    }

    /**
     * Checks if a string contains a given substring.
     * @param string $haystack The string to search in.
     * @param string $needle The substring to search for in the haystack.
     * @return bool Returns **true** if haystack contains needle, **false** otherwise.
     */
    public static function stringContains(string $haystack, string $needle)
    {
        return mb_strpos($haystack, $needle) !== false;
    }

    /**
     * Replaces the first ocurrence of a given substring in a string.
     * @param string $haystack The string to search in.
     * @param string $needle The substring to search for in the haystack.
     * @param string $replace The replacement string.
     * @return string Returns the resulting string.
     */
    public static function replaceFirst(string $haystack, string $needle, string $replace)
    {
        $pos = mb_strpos($haystack, $needle);
        if ($pos !== false) $haystack = substr_replace($haystack, $replace, $pos, mb_strlen($needle));
        return $haystack;
    }

    /**
     * Replaces the last ocurrence of a given substring in a string.
     * @param string $haystack The string to search in.
     * @param string $needle The substring to search for in the haystack.
     * @param string $replace The replacement string.
     * @return string Returns the resulting string.
     */
    public static function replaceLast(string $haystack, string $needle, string $replace)
    {
        $pos = mb_strpos($haystack, $needle);
        if ($pos !== false) $haystack = substr_replace($haystack, $replace, $pos, mb_strlen($needle));
        return $haystack;
    }

    /**
     * Limits the number of characters in a string.
     * @param string $string String to limit.
     * @param int $limit Maximum number of characters to limit.
     * @param string $end (Optional) Text to append to the end of the limited string.
     * @return string Returns the resulting string.
     */
    public static function limitString(string $string, int $limit, string $end = '...')
    {
        if (mb_strwidth($string, 'UTF-8') <= $limit) return $string;
        return rtrim(mb_strimwidth($string, 0, $limit, '', 'UTF-8')) . $end;
    }

    /**
     * Limits the number of words in a string.
     * @param string $string String to limit.
     * @param int $words Maximum number of words to limit.
     * @param string $end (Optional) Text to append to the end of the limited string.
     * @return string Returns the resulting string.
     */
    public static function limitStringWords(string $string, int $words, string $end = '...')
    {
        $string = explode(' ', $string, $words + 1);
        if (isset($string[$words])) unset($string[$words]);
        return implode(' ', $string) . $end;
    }

    /**
     * Encrypts a string using your application secret keys.
     * @param string $string String to encrypt.
     * @param string $method (Optional) Hashing algorithm to use in encryption.
     * @param string|null $token (Optional) Optional token to use in encryption. Leave empty to use your app default token.
     * @return string|bool Returns the encrypted string on success or false on errors.
     */
    public static function encryptString(string $string, string $method = 'sha256', ?string $token = null)
    {
        // Validate method
        if (!in_array($method, hash_algos())) throw new Exception('encryptString(): Invalid hashing algorithm');

        // Get app key and hash it
        $key = Config::get('secret.app_key');
        if (empty($key)) throw new Exception('encryptString(): Application key was not defined');
        $key = hash($method, $key);

        // Get token and hash it
        $token = $token ?? Config::get('secret.app_token');
        if (empty($token)) throw new Exception('encryptString(): Application token was not defined');
        $token = hash($method, $token);

        // Encrypts the string
        $iv = substr($token, 0, 16);
        return openssl_encrypt($string, "AES-256-CBC", $key, 0, $iv);
    }

    /**
     * Decrypts a string using your application secret keys.
     * @param string $string String to decrypt.
     * @param string $method (Optional) Hashing algorithm to use in decryption.
     * @param string|null $token (Optional) Token to use in decryption. Leave empty to use your app default token.
     * @return string|bool Returns the decrypted string on success or false on errors.
     */
    public static function decryptString(string $string, string $method = 'sha256', ?string $token = null)
    {
        // Validate method
        if (!in_array($method, hash_algos())) throw new Exception('decryptString(): Invalid hashing algorithm');

        // Get app key and hash it
        $key = Config::get('secret.app_key');
        if (empty($key)) throw new Exception('decryptString(): Application key was not defined');
        $key = hash($method, $key);

        // Get token and hash it
        $token = $token ?? Config::get('secret.app_token');
        if (empty($token)) throw new Exception('decryptString(): Application token was not defined');
        $token = hash($method, $token);

        // Decrypts the string
        $iv = substr($token, 0, 16);
        return openssl_decrypt($string, "AES-256-CBC", $key, 0, $iv);
    }

    /**
     * Returns the session CSRF token if already exists or creates a new one.
     * @return string Returns the stored or new CSRF token for the current session.
     */
    public static function csrfToken()
    {
        $session = new Session();
        if ($session->has('CSRF_TOKEN')) return $session->get('CSRF_TOKEN');
        $token = self::randomToken();
        $session->set('CSRF_TOKEN', $token);
        return $token;
    }

    /**
     * Generates a random hash token.
     * @param int $length (Optional) Lenght of the token to generate.
     * @return string Returns the resulting token.
     */
    public static function randomToken(int $length = 32)
    {
        return bin2hex(random_bytes(floor($length / 2)));
    }

    /**
     * Generates an unique random hash token.
     * @return string Returns the resulting token.
     */
    public static function uniqueToken()
    {
        return md5(uniqid(self::randomToken(8)));
    }

    /**
     * Generates an universally unique identifier (UUID).
     * @return string Returns the resulting UUID.
     */
    public static function uuid()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
    }

    /**
     * Generates a sortable universally unique identifier (UUID).
     * @return string Returns the resulting UUID.
     */
    public static function orderedUuid()
    {
        $timeHex = str_pad(dechex(floor(microtime(true) * 1000)), 12, "0", STR_PAD_LEFT);
        return sprintf(
            '%s-%s-%04x-%04x-%04x%04x%04x',
            substr($timeHex, 0, 8),
            substr($timeHex, 8, 4) . sprintf('%04x', mt_rand(0, 0xffff)),
            mt_rand(0, 0xffff) . (mt_rand(0, 0x0fff) | 0x4000),
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    /**
     * Generates a random string with a specified length.
     * @param int $length Length of the string to generate.
     * @param bool $letters (Optional) Include lower and uppercase letters in the string.
     * @param bool $numbers (Optional) Include numbers in the string.
     * @param bool $specialchars (Optional) Include special characters in the string.
     * @return string Returns the resulting string.
     */
    public static function randomString(int $length, bool $letters = true, bool $numbers = false, bool $specialchars = false)
    {
        $data = '';
        if ($letters) $data .= 'abcdefghijklmnopqABCDEFGHIJKLMNOPQ';
        if ($numbers) $data .= '0123456789';
        if ($specialchars) $data .= '!@#$%&*(){}[]-+=/.,;:?\\|_';
        return substr(str_shuffle($data), 0, $length);
    }

    /**
     * Generates a random nunber with a specified length.
     * @param int $length Lenght of the generated number.
     * @param bool $int (Optional) Return the number as an integer instead of a string.
     * @return string|int Returns the resulting number.
     */
    public static function randomNumber(int $length, bool $int = false)
    {
        $result = self::randomString($length, false, true);
        return $int ? (int)$result : $result;
    }

    /**
     * Converts UTF-8 accented characters from a string into its non-accented equivalents.
     * @param string $string String to convert.
     * @return string Returns the resulting string.
     */
    public static function stripAccents(string $string)
    {
        $accents = 'àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ';
        $replace = 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY';
        return utf8_encode(strtr(utf8_decode($string), utf8_decode($accents), $replace));
    }

    /**
     * Converts a string into a valid URI slug format.
     * @param string $string String to convert.
     * @param string $separator (Optional) Separator to use to replace spaces (and invalid characters if `$keepOther` is true).
     * @param bool $keepOther (Optional) Keep invalid characters replacing them with the separator instead of removing.
     * @return string Returns the resulting string.
     */
    public static function slug(string $string, string $separator = '-', bool $keepOther = false)
    {
        $string = self::stripAccents(mb_strtolower($string));
        $string = str_replace(' ', $separator, $string);
        $string = preg_replace('/[^a-zA-Z0-9' . preg_quote($separator) . ']/u', ($keepOther ? $separator : ''), $string);
        return $string;
    }

    /**
     * Converts a string into **kebab-case** convention. It also removes all accents and characters that are not\
     * valid letters, numbers or dashes, and converts spaces into dashes.
     * @param string $string String to convert.
     * @return string Returns the resulting string.
     */
    public static function kebabCase(string $string)
    {
        $string = preg_replace('/(.)(?=[A-Z])/u', '$1-', $string);
        return self::slug($string);
    }

    /**
     * Converts a string into **snake_case** convention. It also removes all accents and characters that are not\
     * valid letters, numbers or underscores, and converts spaces into underscores.
     * @param string $string String to convert.
     * @return string Returns the resulting string.
     */
    public static function snakeCase(string $string)
    {
        $string = preg_replace('/(.)(?=[A-Z])/u', '$1_', $string);
        return self::slug($string, '_');
    }

    /**
     * Converts a string into **camelCase** convention. It also removes all accents and characters that are not\
     * valid letters, numbers or underscores.
     * @param string $string String to be converted.
     * @return string Returns the resulting string.
     */
    public static function camelCase(string $string)
    {
        $string = self::stripAccents($string);
        $string = preg_replace('/[^a-zA-Z0-9_]/u', ' ', $string);
        return str_replace(' ', '', lcfirst(ucwords($string)));
    }

    /**
     * Converts a string into **PascalCase** convention. It also removes all accents and characters that are not\
     * valid letters, numbers or underscores.
     * @param string $string String to be converted.
     * @return string Returns the resulting string.
     */
    public static function pascalCase(string $string)
    {
        return ucfirst(self::camelCase($string));
    }

    /**
     * Converts an English singular word into plural.
     * @param string $word Word to be converted.
     * @return string Returns the pluralized string.
     */
    public static function pluralize(string $word)
    {
        $last = strtolower($word[strlen($word) - 1]);
        $last2 = strtolower(substr($word, -2));
        if (substr($word, -3) === 'ies' || substr($word, -3) === 'ves' || substr($word, -2) === 'es' || substr($word, -1) === 's') return $word;
        if ($last === 'y' && !in_array(strtolower($word[strlen($word) - 2]), ['a', 'e', 'i', 'o', 'u'])) return substr($word, 0, -1) . 'ies';
        if ($last2 === 'us') return substr($word, 0, -2) . 'i';
        if ($last2 === 'is') return substr($word, 0, -2) . 'es';
        if ($last2 === 'on') return substr($word, 0, -2) . 'a';
        if (in_array($last, ['s', 'x', 'z', 'o']) || ($last === 'h' && in_array(strtolower($word[strlen($word) - 2]), ['c', 's']))) return $word . 'es';
        return $word . 's';
    }

    /**
     * Converts an English plural word into singular.
     * @param string $word Word to be converted.
     * @return string Returns the singularized string.
     */
    public static function singularize(string $word)
    {
        $last = strtolower($word[strlen($word) - 1]);
        $last2 = strtolower(substr($word, -2));
        if (!in_array($last, ['s', 'x', 'z']) && substr($word, -3) !== 'ies' && substr($word, -3) !== 'ves' && $last2 !== 'es') return $word;
        if ($last2 === 'es') {
            if (substr($word, -4) === 'sses' || substr($word, -4) === 'shes' || substr($word, -4) === 'ches') return substr($word, 0, -2);
            if (substr($word, -3) === 'ies') return substr($word, 0, -3) . 'y';
            if (substr($word, -3) === 'xes' || substr($word, -3) === 'ses' || substr($word, -3) === 'zes') return substr($word, 0, -2);
            if (substr($word, -3) !== 'oes') return substr($word, 0, -2);
        }
        if (substr($word, -3) === 'ies') return substr($word, 0, -3) . 'y';
        if (substr($word, -3) === 'ves') return substr($word, 0, -3) . 'f';
        if ($last2 === 'us') return substr($word, 0, -2) . 'us';
        if ($last2 === 'on') return substr($word, 0, -2) . 'on';
        if ($last === 's') return substr($word, 0, -1);
        return $word;
    }

    /**
     * Validates if a string is a valid JSON string.
     * @param mixed $string String to be validated.
     * @return bool Returns true if valid JSON, false otherwise.
     */
    public static function isJson($string)
    {
        if (!is_string($string)) return false;
        try {
            json_decode($string, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $th) {
            return false;
        }
        return true;
    }

    /**
     * Serializes a variable to JSON with predefined flags. Also checks for Elements to correctly convert them.
     * @param mixed $data Variable to be encoded.
     * @param int $flags (Optional) JSON encoding flags (same as in `json_encode()` function).
     * @param int $depth (Optional) JSON encoding maximum depth (same as in `json_encode()` function).
     * @return string The resulting JSON string.
     */
    public static function jsonEncode($data, int $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES, int $depth = 512)
    {
        if (is_callable([$data, 'toJson'])) return $data->toJson($flags, $depth);
        return json_encode($data, $flags, $depth);
    }

    /**
     * Masks a string with a repeated character.
     * @param string $string String to be masked.
     * @param string $character Character to use in mask.
     * @param int $index Index of the string to start masking.
     * @param int|null $length Masking length. Leave empty to mask the rest of the string.
     * @return string Returns the masked string.
     */
    public static function maskString(string $string, string $character, int $index, ?int $length = null)
    {
        $segment = mb_substr($string, $index, $length, 'UTF-8');
        $strlen = mb_strlen($string, 'UTF-8');
        $startIndex = $index;
        if ($index < 0) $startIndex = $index < -$strlen ? 0 : $strlen + $index;
        $start = mb_substr($string, 0, $startIndex, 'UTF-8');
        $segmentLen = mb_strlen($segment, 'UTF-8');
        $end = mb_substr($string, $startIndex + $segmentLen);
        return $start . str_repeat(mb_substr($character, 0, 1, 'UTF-8'), $segmentLen) . $end;
    }

    /**
     * Returns a list of all files in a directory and its subdirectories matching a pattern.
     * @param string $pattern Valid pathname pattern, same as used in `glob()`.
     * @return array Returns an array with the filenames.
     */
    public static function getFiles(string $pattern)
    {
        $files = glob($pattern) ?? [];
        $folders = glob(dirname($pattern) . '/*', GLOB_ONLYDIR) ?? [];
        foreach ($folders as $dir) $files = array_merge($files, self::getFiles($dir . '/' . basename($pattern)));
        return $files;
    }

    /**
     * Sanitizes a filename against directory traversal attacks.
     * @param string $filename Filename to sanitize.
     * @return string Returns the sanitized filename.
     */
    public static function sanitizeFilename(string $filename)
    {
        return trim(str_replace(['../', '..\\', './', '.\\'], '', urldecode($filename)), '/');
    }

    /**
     * Checks if a variable is empty.\
     * A numeric/bool safe version of PHP `empty()` function.
     * @var mixed $variable Variable to be checked.
     * @return bool Returns true if the variable is empty, false otherwise.
     */
    public static function isEmpty($variable)
    {
        if (!isset($variable)) return true;
        if (is_numeric($variable) || is_bool($variable)) return false;
        if (is_string($variable)) return trim($variable) === '';
        if ($variable instanceof Countable) return count($variable) === 0;
        return empty($variable);
    }

    /**
     * Gets the size of a variable. For strings, the text length. For files, the filesize in kilobytes.\
     * For countable objects/arrays, the number of items. For numeric values, the number itself.
     * @param mixed $variable Variable to get the size from.
     * @return int Returns the variable size depending on its type.
     */
    public static function getSize($variable)
    {
        if ($variable instanceof Countable) {
            return count($variable);
        } else if (is_string($variable) && is_file($variable)) {
            return (round(filesize($variable) / 1024, 2));
        } else if (is_string($variable)) {
            return mb_strlen($variable);
        } else if (is_numeric($variable)) {
            return $variable;
        }
    }

    /**
     * Returns the basename from a class (without its namespace).
     * @param string|object $class Classname or an object to get the class.
     * @return string Returns the class basename.
     */
    public static function classname($class)
    {
        $class = is_object($class) ? get_class($class) : $class;
        return basename(str_replace('\\', '/', $class));
    }

    /**
     * Returns if a class uses a trait.
     * @param string|object $class Classname or an object to get the class.
     * @param string $trait Trait classname.
     * @return bool Returns if the class/object uses the trait.
     */
    public static function usesTrait($class, string $trait)
    {
        if (is_string($class) || is_object($class)) {
            if (is_string($class) && !class_exists($class)) return false;
            $parentClasses = class_parents($class);
            $traits = class_uses($class);
            foreach ($parentClasses as $parentClass) $traits = array_merge($traits, class_uses($parentClass));
            return in_array($trait, $traits);
        } else {
            return false;
        }
    }

    /**
     * Returns if the application is running through command-line console.
     * @return bool Returns true if CLI, false otherwise.
     */
    public static function isCLI()
    {
        return defined('STDIN') || (empty($_SERVER['REMOTE_ADDR']) && !isset($_SERVER['HTTP_USER_AGENT']) && count($_SERVER['argv']) > 0);
    }
}
