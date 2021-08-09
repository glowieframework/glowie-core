<?php
    use Glowie\Core\Http\Rails;
    use Glowie\Core\Http\Session;
    use Glowie\Core\Http\Response;

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
            return '1.0.0';
        }

        /**
         * Prints a variable in an human-readable way.
         * @param mixed $var Variable to be printed.
         * @param bool $exit (Optional) Stop code execution after printing.
         * @return void
         */
        public static function log($var){
            Rails::getResponse()->setContentType(Response::CONTENT_HTML);
            echo '<pre style="white-space: pre-wrap; word-wrap: break-all; background-color: #f5f5f5; border: 1px solid gainsboro; padding: 15px; margin: 0;">';
            var_dump($var);
            echo '</pre>';
            die();
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
            if(empty($route)) throw new Exception('route: Route name cannot be empty');
            if (!is_array($params)) throw new Exception('route: $params must be an array');

            // Gets the named route
            $routeData = Rails::getRoute($route);
            if(empty($routeData)) throw new Exception('route: Route name "' . $route .'" does not match any existing route');

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
            if (!empty($missing)) throw new Exception('route: Missing parameter "' . implode('", "', $missing) . '" for route "' . $route . '"');

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
         * @param string $key Key to use as the reordering base.
         * @param int $order (Optional) Ordering direction, can be `SORT_ASC` (ascending) or `SORT_DESC` (descending).
         * @return array Returns the reordered array.
         */
        public static function orderArray(array $array, string $key, int $order = SORT_ASC){
            if (!empty($array)) {
                foreach ($array as $col => $row) $data[$col] = $row[$key];
                array_multisort($data, $order, $array);
            }
            return $array;
        }

        /**
         * Filters an associative array leaving only elements that matches a specific key value.
         * @param array $array Array to filter.
         * @param string $key Key to use as the filtering base.
         * @param mixed $value Value to filter.
         * @return array Returns the filtered array.
         */
        public static function filterArray(array $array, string $key, $value){
            $newarray = [];
            if (!empty($array)) {
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
         * @param string $key Key to match value.
         * @param mixed $value Value to search.
         * @return array Returns the first array item found.
         */
        public static function searchArray(array $array, string $key, $value){
            $result = [];
            if (!empty($array)) {
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
            if (!$length) return true;
            return substr($haystack, -$length) == $needle;
        }

        /**
         * Encrypts a string using your application secret keys.
         * @param string $string String to encrypt.
         * @param string $method (Optional) Hashing algorithm to use in encryption.
         * @return string|bool Returns the encrypted string on success or false on errors.
         */
        public static function encryptString(string $string, string $method = 'sha256'){
            $key = hash($method, GLOWIE_CONFIG['app_key']);
            $iv = substr(hash($method, GLOWIE_CONFIG['app_token']), 0, 16);
            $hash = openssl_encrypt($string, "AES-256-CBC", $key, 0, $iv);
            if(!$hash) return false;
            return base64_encode($hash);
        }

        /**
         * Decrypts a string using your application secret keys.
         * @param string $string String to decrypt.
         * @param string $method (Optional) Hashing algorithm to use in decryption.
         * @return string|bool Returns the decrypted string on success or false on errors.
         */
        public static function decryptString(string $string, string $method = 'sha256'){
            $key = hash($method, GLOWIE_CONFIG['app_key']);
            $iv = substr(hash($method, GLOWIE_CONFIG['app_token']), 0, 16);
            $hash = base64_decode($string);
            if(!$hash) return false;
            return openssl_decrypt($hash, "AES-256-CBC", $key, 0, $iv);;
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
         * Converts a string to a valid slug URI format. It also removes all accents and characters that are not\
         * valid letters, numbers or dashes, and converts spaces into dashes.
         * @param string $string String to convert.
         * @return string Returns the converted string.
         */
        public static function slug(string $string){
            $string = strtr(utf8_decode(strtolower($string)), utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
            $string = str_replace(' ', '-', $string);
            $string = preg_replace('/[^a-zA-Z0-9-]/', '', $string);
            return $string;
        }

        /**
         * Converts a string to **snake_case** convention. It also removes all accents and characters that are not\
         * valid letters, numbers or underscores, and converts spaces into underscores.
         * @param string $string String to convert.
         * @return string Returns the converted string.
         */
        public static function snakeCase(string $string){
            $string = strtr(utf8_decode(strtolower($string)), utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
            $string = str_replace(' ', '_', $string);
            $string = preg_replace('/[^a-zA-Z0-9_]/', '', $string);
            return $string;
        }

        /**
         * Converts a string to **camelCase** convention. It also removes all accents and characters that are not\
         * valid letters, numbers or underscores.
         * @param string $string String to be converted.
         * @return string Returns the converted string.
         */
        public static function camelCase(string $string){
            $string = strtr(utf8_decode(strtolower($string)), utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
            $string = preg_replace('/[^a-zA-Z0-9_]/', ' ', $string);
            return str_replace(' ', '', lcfirst(ucwords($string)));
        }

        /**
         * Converts a string to **PascalCase** convention. It also removes all accents and characters that are not\
         * valid letters, numbers or underscores.
         * @param string $string String to be converted.
         * @return string Returns the converted string.
         */
        public static function pascalCase(string $string){
            return ucfirst(self::camelCase($string));
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