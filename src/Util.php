<?php
    use Glowie\Core\Rails;
    use Glowie\Core\Session;

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
            $uri = [];
            $result = [];
            $missing = [];
            foreach(explode('/', $routeData['uri']) as $segment){
                if(self::startsWith($segment, ':')){
                    $segment = substr($segment, 1);
                    $result[] = $segment;
                    if(isset($params[$segment])){
                        $uri[] = $params[$segment];
                    }else{
                        $missing[] = $segment;
                    }
                }else{
                    $uri[] = $segment;
                }
            }

            // Validates missing parameters
            if (!empty($missing)) trigger_error('route: Missing parameter "' . implode('", "', $missing) . '" for route "' . $route . '"', E_USER_ERROR);

            // Checks if the route has any parameters
            if(!empty($result)){
                // Parses remaining parameters and returns result
                $remaining = array_diff_key($params, array_flip($result));
                return self::baseUrl(implode('/', $uri) . (!empty($remaining) ? '?' . http_build_query($remaining) : ''));
            }else{
                // Returns result
                return self::baseUrl(implode('/', $uri) . (!empty($params) ? '?' . http_build_query($params) : ''));
            }
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
         * Returns the session CSRF token if already exists or creates a new one.
         * @return string The stored or new CSRF token for the current session.
         */
        public static function csrfToken(){
            $session = new Session();
            if($session->has('CSRF_TOKEN')) return $session->get('CSRF_TOKEN');
            $token = bin2hex(random_bytes(32));
            $session->set('CSRF_TOKEN', $token);
            return $token;
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
         * Converts a string to a valid friendly URI format.
         * @param string $string String to convert.
         * @return string Returns the friendly URI.
         */
        public static function friendlyUri(string $string){
            $string = strtr(utf8_decode(strtolower($string)), utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
            $string = str_replace(' ', '-', $string);
            $string = preg_replace('/[^a-zA-Z0-9-]/', '', $string);
            return $string;
        }

        /**
         * Converts a string to camelCase. It also removes all accents and characters that are not\
         * valid letters, numbers or underscores.
         * @param string $string String to be converted.
         * @param bool $firstUpper (Optional) Determines if the first character should be uppercased (PascalCase).
         * @return string Returns the converted string.
         */
        public static function camelCase(string $string, bool $firstUpper = false){
            $string = strtr(utf8_decode(strtolower($string)), utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
            $string = preg_replace('/[^a-zA-Z0-9_]/', ' ', $string);
            if($firstUpper){
                return str_replace(' ', '', ucwords($string));
            }else{
                return str_replace(' ', '', lcfirst(ucwords($string)));
            }
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