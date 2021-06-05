<?php
    use Glowie\Core\Rails;

    /**
     * Miscellaneous utilities for Glowie application.
     * @category Utility
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.0
     */
    class Util{

        /**
         * Returns current Glowie core version.
         * @return string Current Glowie core version.
         */
        public static function getVersion(){
            return '1.0';
        }

        /**
         * Prints a variable in an human-readable way.
         * @param mixed $var Variable to be printed.
         * @param bool $exit (Optional) Stop code execution after printing.
         */
        public static function log($var, bool $exit = false){
            echo '<pre>';
            print_r($var);
            echo '</pre>';
            if($exit) exit;
        }

        /**
         * Returns the full URL relative to the application path.
         * @param string $path (Optional) Path to append to the base URL.
         * @return string Full base URL.
         */
        public static function baseUrl(string $path = ''){
            return GLOWIE_BASE_URL . trim($path, '/');
        }

        /**
         * Returns the current URL.
         * @param $query (Optional) Include query string parameters.
         * @return string Current URL.
         */
        public static function currentUrl($query = true){
            return (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . ($query ? $_SERVER['REQUEST_URI'] : parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
        }

        /**
         * Returns the previous URL where the user was.\
         * **Note:** This information relies in the `HTTP_REFERER` header. This header cannot be sent\
         * from some browsers or be unavailable when using different HTTP protocols.
         */
        public static function previousUrl(){
            return $_SERVER['HTTP_REFERER'] ?? '';
        }

        /**
         * Returns the base URL from a named route.
         * @param string $route Route internal name/identifier.
         * @param array $params (Optional) Route parameters to bind into the URL.
         * @return string Full URL relative to the application path.
         */
        public static function route(string $route, array $params = []){
            // Validate arguments
            if(empty($route)) trigger_error('route: Route name cannot be empty', E_USER_ERROR);
            if (!is_array($params)) trigger_error('route: $params must be an array', E_USER_ERROR);

            // Gets the named route
            $routeData = Rails::getRoute($route);
            if(empty($routeData)) trigger_error('route: Route name "' . $route .'" does not match any existing route', E_USER_ERROR);

            // Gets the route parameters
            $uri = explode('/:', $routeData['uri']);

            // Checks if the route has any parameters
            if(count($uri) > 1){
                unset($uri[0]);
                $uri = array_flip($uri);

                // Validates route parameters
                $missing = array_diff_key($uri, $params);
                if (!empty($missing)) trigger_error('route: Missing parameter "' . implode('", "', array_keys($missing)) . '" for route "' . $route . '"', E_USER_ERROR);

                // Parses remaining parameters
                $remaining = array_diff_key($params, $uri);

                // Returns result
                if (!empty($remaining)) {
                    return self::baseUrl(implode('/', array_intersect_key($params, $uri)) . '?' . http_build_query($remaining));
                } else {
                    return self::baseUrl(implode('/', array_intersect_key($params, $uri)));
                }
            }else{
                // Returns result
                if(!empty($params)){
                    return self::baseUrl(implode('/', $uri) . '?' . http_build_query($params));
                }else{
                    return self::baseUrl(implode('/', $uri));
                }
            }
        }

        /**
         * Redirects to a relative or full URL.
         * @param string $destination Target URL to redirect to.
         * @param int $code (Optional) HTTP response code to pass with the redirect.
         * @return void
         */
        public static function redirect(string $destination, int $code = 302){
            header('Location: ' . $destination, true, $code);
            die();
        }

        /**
         * Reorders an associative array by a specific key value.
         * @param array $array Array to reorder.
         * @param mixed $key Key to use as reordering base.
         * @param int $order (Optional) Ordering direction, can be `SORT_ASC` (ascending) or `SORT_DESC` (descending).
         * @return array Returns the reordered array.
         */
        public static function orderArray(array $array, $key, int $order = SORT_ASC){
            if ((!empty($array)) && (!empty($key))) {
                if (is_array($array)) {
                    foreach ($array as $col => $row) {
                        $data[$col] = $row[$key];
                    }
                    array_multisort($data, $order, $array);
                }
            }
            return $array;
        }

        /**
         * Filters an associative array with elements that matches a specific key value.
         * @param array $array Array to filter.
         * @param mixed $key Key to use as filtering base.
         * @param mixed $value Value to filter.
         * @return array Returns the filtered array.
         */
        public static function filterArray(array $array, $key, $value){
            $newarray = [];
            if (is_array($array) && count($array) > 0) {
                foreach (array_keys($array) as $col) {
                    $temp[$col] = $array[$col][$key];
                    if ($temp[$col] == $value) $newarray[$col] = $array[$col];
                }
            }
            return $newarray;
        }

        /**
         * Searches an array of associative arrays for the first item that matches a specific key value.
         * @param array $array Array to search.
         * @param mixed $key Key to match value.
         * @param mixed $value Value to search.
         * @return array Returns the first array found.
         */
        public static function searchArray(array $array, $key, $value){
            $result = [];
            if (is_array($array) && count($array) > 0) {
                $index = array_search($value, array_column($array, $key));
                if ($index !== false) $result = $array[$index];
            }
            return $result;
        }

        /**
         * Checks if a string starts with a given substring.
         * @param string $haystack The string to search in.
         * @param string $needle The substring to search for in the haystack.
         * @return bool Returns **true** if haystack begins with needle, **false** otherwise.
         */
        public static function startsWith(string $haystack, string $needle){
            $length = strlen($needle);
            return substr($haystack, 0, $length) == $needle;
        }

        /**
         * Checks if a string ends with a given substring.
         * @param string $haystack The string to search in.
         * @param string $needle The substring to search for in the haystack.
         * @return bool Returns **true** if haystack ends with needle, **false** otherwise.
         */
        public static function endsWith(string $haystack, string $needle){
            $length = strlen($needle);
            if (!$length) {
                return true;
            }
            return substr($haystack, -$length) == $needle;
        }

        /**
         * Encrypts a string using your application secret keys.
         * @param string $string String to encrypt.
         * @return string Returns the encrypted string.
         */
        public static function encryptString(string $string){
            $key = hash('sha256', GLOWIE_CONFIG['api_key']);
            $iv = substr(hash('sha256', GLOWIE_CONFIG['api_token']), 0, 16);
            $string = base64_encode(openssl_encrypt($string, "AES-256-CBC", $key, 0, $iv));
            return $string;
        }

        /**
         * Decrypts a string using your application secret keys.
         * @param string $string String to decrypt.
         * @return string Returns the decrypted string.
         */
        public static function decryptString(string $string){
            $key = hash('sha256', GLOWIE_CONFIG['api_key']);
            $iv = substr(hash('sha256', GLOWIE_CONFIG['api_token']), 0, 16);
            $string = openssl_decrypt(base64_decode($string), "AES-256-CBC", $key, 0, $iv);
            return $string;
        }

        /**
         * Generates a random string with a specified length.
         * @param int $length Length of the string to generate.
         * @param bool $letters (Optional) Include lower and uppercase letters in the string.
         * @param bool $numbers (Optional) Include numbers in the string.
         * @param bool $specialchars (Optional) Include special characters in the string.
         * @return string Returns the random string.
         */
        public static function randomString(int $length, bool $letters = true, bool $numbers = false, bool $specialchars = false){
            $data = '';
            if($letters) $data .= 'abcdefghijklmnopqABCDEFGHIJKLMNOPQ';
            if($numbers) $data .= '0123456789';
            if($specialchars) $data .= '!@#$%&*(){}[]-+=/.,;:?\\|_';
            return substr(str_shuffle($data), 0, $length);
        }

        /**
         * Returns a list of all files in a directory and its subdirectories matching a pattern.
         * @param string $pattern Valid pathname pattern, same as used in `glob()`.
         * @return array Array with the filenames.
         */
        public static function getFiles(string $pattern){
            $files = glob($pattern);
            foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
                $files = array_merge($files, self::getFiles($dir . '/' . basename($pattern)));
            }
            return $files;
        }

    }

?>