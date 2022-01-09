<?php
    use Glowie\Core\Http\Rails;
    use Glowie\Core\Http\Session;
    use Glowie\Core\Http\Response;
    use Glowie\Core\View\Buffer;
    use Glowie\Core\Exception\FileException;
    use Glowie\Core\CLI\Firefly;

    /**
     * Miscellaneous utilities for Glowie application.
     * @category Utility
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.1
     */
    class Util{

        /**
         * Returns the current Glowie core version.
         * @return string Current Glowie core version.
         */
        public static function getVersion(){
            return '1.1.1';
        }

        /**
         * Dumps a variable in a human-readable way and ends the script execution.
         * @param mixed $var Variable to be dumped.
         * @param bool $plain (Optional) Dump variable as plain text instead of HTML.
         * @return void
         */
        public static function dump($var, bool $plain = false){
            // Clean output buffer
            Buffer::clean();

            // Dumps the content
            if(Firefly::isCLI()){
                var_dump($var);
            }else if($plain){
                Rails::getResponse()->setContentType(Response::CONTENT_PLAIN);
                var_dump($var);
            }else{
                Rails::getResponse()->setContentType(Response::CONTENT_HTML);
                include(__DIR__ . '/Views/dump.phtml');
            }

            // Stop script
            die();
        }

        /**
         * Returns the absolute URL of the application path.
         * @param string $path (Optional) Relative path to append to the base URL.
         * @return string Full base URL.
         */
        public static function baseUrl(string $path = ''){
            return APP_BASE_URL . trim($path, '/');
        }

        /**
         * Returns the base URL of a named route.
         * @param string $route Route name.
         * @param array $params (Optional) Route parameters to bind into the URL.
         * @return string Returns the absolute URL of the application path with the route appended.
         */
        public static function route(string $route, array $params = []){
            // Gets the named route
            $routeData = Rails::getRoute($route);
            if(empty($routeData)) throw new Exception('route(): Route name "' . $route .'" does not match any existing route');

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
            if (!empty($missing)) throw new Exception('route(): Missing parameter "' . implode('", "', $missing) . '" for route "' . $route . '"');

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
         * Returns the base URL of an asset file with a token to force cache reloading on browsers.
         * @param string $filename Asset filename. Must be a path relative to the **app/public/assets** folder.
         * @param string $token (Optional) Token parameter name to append to the filename.
         * @return string Returns the absolute URL of the asset file with the token.
         */
        public static function asset(string $filename, string $token = 'assetVersion'){
            $filename = 'assets/' . trim($filename, '/');
            if(!is_file($filename)) throw new FileException('Asset file "app/public/' . $filename . '" was not found');
            return self::baseUrl(sprintf('%s?%s=%s', $filename, $token, md5(filemtime($filename))));
        }

        /**
         * Reorders a multi-dimensional array by a key value.
         * @param array $array Array to reorder.
         * @param mixed $key Key to use as the reordering base.
         * @param int $order (Optional) Ordering direction: `SORT_ASC` (ascending) or `SORT_DESC` (descending).
         * @return array Returns the resulting array.
         */
        public static function orderArray(array $array, $key, int $order = SORT_ASC){
            if (!empty($array)) {
                foreach ($array as $col => $row) $data[$col] = $row[$key];
                array_multisort($data, $order, $array);
            }
            return $array;
        }

        /**
         * Filters a multi-dimensional array leaving only items that match a key value.
         * @param array $array Array to filter.
         * @param mixed $key Key to use as the filtering base.
         * @param mixed $value Value to filter.
         * @return array Returns the resulting array.
         */
        public static function filterArray(array $array, $key, $value){
            $result = [];
            if (!empty($array)) {
                foreach (array_keys($array) as $col) {
                    $temp[$col] = $array[$col][$key];
                    if ($temp[$col] == $value) $result[$col] = $array[$col];
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
        public static function searchArray(array $array, $key, $value){
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
        public static function dotArray(array $array){
            $result = [];
            if(!empty($array)){
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
        public static function undotArray(array $array){
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
         * Returns a random item from an array.
         * @param array $array Array to pick a random item.
         * @return mixed Returns the random item value.
         */
        public static function randomArray(array $array){
            return $array[array_rand($array)];
        }

        /**
         * Returns a value from a multi-dimensional array in dot notation.
         * @param array $array Array to get the value.
         * @param mixed $key Key to get in dot notation.
         * @param mixed $default (Optional) Default value to return if the key does not exist.
         * @return mixed Returns the value if exists or the default if not.
         */
        public static function arrayGet(array $array, $key, $default = null){
            // Checks if the key does not exist already
            if(isset($array[$key])) return $array[$key];

            // Loops through each key
            foreach(explode('.', $key) as $segment){
                if(!is_array($array) || !isset($array[$segment])) return $default;
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
        public static function arraySet(array &$array, $key, $value){
            $item = &$array;
            foreach(explode('.', $key) as $segment){
                if(isset($item[$segment]) && !is_array($item[$segment])) $item[$segment] = [];
                $item = &$item[$segment];
            }
            $item = $value;
        }

        /**
         * Returns a value from a multi-dimensional object in dot notation.
         * @param object $object Object to get the value.
         * @param string $key Key to get in dot notation.
         * @param mixed $default (Optional) Default value to return if the key does not exist.
         * @return mixed Returns the value if exists or the default if not.
         */
        public static function objectGet(object $object, string $key, $default = null){
            // Checks if the key does not exist already
            if(isset($object->{$key})) return $object->{$key};

            // Loops through each property
            foreach(explode('.', $key) as $segment){
                if(!is_object($object) || !isset($object->{$segment})) return $default;
                $object = $object->{$segment};
            }

            // Returns the value
            return $object;
        }

        /**
         * Checks if a string starts with a given substring.
         * @param string $haystack The string to search in.
         * @param string $needle The substring to search for in the haystack.
         * @return bool Returns **true** if haystack begins with needle, **false** otherwise.
         */
        public static function startsWith(string $haystack, string $needle){
            $length = mb_strlen($needle);
            return substr($haystack, 0, $length) == $needle;
        }

        /**
         * Checks if a string ends with a given substring.
         * @param string $haystack The string to search in.
         * @param string $needle The substring to search for in the haystack.
         * @return bool Returns **true** if haystack ends with needle, **false** otherwise.
         */
        public static function endsWith(string $haystack, string $needle){
            $length = mb_strlen($needle);
            if (!$length) return true;
            return substr($haystack, -$length) == $needle;
        }

        /**
         * Checks if a string contains a given substring.
         * @param string $haystack The string to search in.
         * @param string $needle The substring to search for in the haystack.
         * @return bool Returns **true** if haystack contains needle, **false** otherwise.
         */
        public static function stringContains(string $haystack, string $needle){
            return strpos($haystack, $needle) !== false;
        }

        /**
         * Replaces the first ocurrence of a given substring in a string.
         * @param string $haystack The string to search in.
         * @param string $needle The substring to search for in the haystack.
         * @param string $replace The replacement string.
         * @return string Returns the resulting string.
         */
        public static function replaceFirst(string $haystack, string $needle, string $replace){
            $pos = strpos($haystack, $needle);
            if($pos !== false) $haystack = substr_replace($haystack, $replace, $pos, mb_strlen($needle));
            return $haystack;
        }

        /**
         * Replaces the last ocurrence of a given substring in a string.
         * @param string $haystack The string to search in.
         * @param string $needle The substring to search for in the haystack.
         * @param string $replace The replacement string.
         * @return string Returns the resulting string.
         */
        public static function replaceLast(string $haystack, string $needle, string $replace){
            $pos = strrpos($haystack, $needle);
            if($pos !== false) $haystack = substr_replace($haystack, $replace, $pos, mb_strlen($needle));
            return $haystack;
        }

        /**
         * Limits the number of characters in a string.
         * @param string $string String to limit.
         * @param int $limit Maximum number of characters to limit.
         * @param string $end (Optional) Text to append to the end of the limited string.
         * @return string Returns the resulting string.
         */
        public static function limitString(string $string, int $limit, string $end = '...'){
            if (mb_strwidth($string, 'UTF-8') <= $limit) return $string;
            return rtrim(mb_strimwidth($string, 0, $limit, '', 'UTF-8')) . $end;
        }

        /**
         * Encrypts a string using your application secret keys.
         * @param string $string String to encrypt.
         * @param string $method (Optional) Hashing algorithm to use in encryption.
         * @return string|bool Returns the encrypted string on success or false on errors.
         */
        public static function encryptString(string $string, string $method = 'sha256'){
            $key = hash($method, Config::get('secret.app_key', 'f08e8ba131c7abab97dba275fab5a85e'));
            $iv = substr(hash($method, Config::get('secret.app_token', 'd147723d9e91340d9dd28fbd5a0b6651')), 0, 16);
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
            $key = hash($method, Config::get('secret.app_key', 'f08e8ba131c7abab97dba275fab5a85e'));
            $iv = substr(hash($method, Config::get('secret.app_token', 'd147723d9e91340d9dd28fbd5a0b6651')), 0, 16);
            $hash = base64_decode($string);
            if(!$hash) return false;
            return openssl_decrypt($hash, "AES-256-CBC", $key, 0, $iv);;
        }

        /**
         * Returns the session CSRF token if already exists or creates a new one.
         * @return string Returns the stored or new CSRF token for the current session.
         */
        public static function csrfToken(){
            $session = new Session();
            if($session->has('CSRF_TOKEN')) return $session->get('CSRF_TOKEN');
            $token = self::randomToken();
            $session->set('CSRF_TOKEN', $token);
            return $token;
        }

        /**
         * Generates a random hash token.
         * @param int $length (Optional) Lenght of the token to generate.
         * @return string Returns the resulting token.
         */
        public static function randomToken(int $length = 32){
            return bin2hex(random_bytes(floor($length / 2)));
        }

        /**
         * Generates a random string with a specified length.
         * @param int $length Length of the string to generate.
         * @param bool $letters (Optional) Include lower and uppercase letters in the string.
         * @param bool $numbers (Optional) Include numbers in the string.
         * @param bool $specialchars (Optional) Include special characters in the string.
         * @return string Returns the resulting string.
         */
        public static function randomString(int $length, bool $letters = true, bool $numbers = false, bool $specialchars = false){
            $data = '';
            if($letters) $data .= 'abcdefghijklmnopqABCDEFGHIJKLMNOPQ';
            if($numbers) $data .= '0123456789';
            if($specialchars) $data .= '!@#$%&*(){}[]-+=/.,;:?\\|_';
            return substr(str_shuffle($data), 0, $length);
        }

        /**
         * Converts UTF-8 accented characters from a string into its non-accented equivalents.
         * @param string $string String to convert.
         * @return string Returns the resulting string.
         */
        public static function stripAccents(string $string){
            $accents = 'àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ';
            $replace = 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY';
            return strtr(utf8_decode($string), utf8_decode($accents), $replace);
        }

        /**
         * Converts a string into a valid URI slug format. It also removes all accents and characters that are not\
         * valid letters, numbers or dashes, and converts spaces into dashes.
         * @param string $string String to convert.
         * @param string $separator (Optional) Separator to use to replace spaces.
         * @return string Returns the resulting string.
         */
        public static function slug(string $string, string $separator = '-'){
            $string = self::stripAccents(strtolower($string));
            $string = str_replace(' ', $separator, $string);
            $string = preg_replace('/[^a-zA-Z0-9' . preg_quote($separator) . ']/', '', $string);
            return $string;
        }

        /**
         * Converts a string into **snake_case** convention. It also removes all accents and characters that are not\
         * valid letters, numbers or underscores, and converts spaces into underscores.
         * @param string $string String to convert.
         * @return string Returns the resulting string.
         */
        public static function snakeCase(string $string){
            return self::slug($string, '_');
        }

        /**
         * Converts a string into **camelCase** convention. It also removes all accents and characters that are not\
         * valid letters, numbers or underscores.
         * @param string $string String to be converted.
         * @return string Returns the resulting string.
         */
        public static function camelCase(string $string){
            $string = self::stripAccents($string);
            $string = preg_replace('/[^a-zA-Z0-9_]/', ' ', $string);
            return str_replace(' ', '', lcfirst(ucwords($string)));
        }

        /**
         * Converts a string into **PascalCase** convention. It also removes all accents and characters that are not\
         * valid letters, numbers or underscores.
         * @param string $string String to be converted.
         * @return string Returns the resulting string.
         */
        public static function pascalCase(string $string){
            return ucfirst(self::camelCase($string));
        }

        /**
         * Returns a list of all files in a directory and its subdirectories matching a pattern.
         * @param string $pattern Valid pathname pattern, same as used in `glob()`.
         * @return array Returns an array with the filenames.
         */
        public static function getFiles(string $pattern){
            $files = glob($pattern);
            foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
                $files = array_merge($files, self::getFiles($dir . '/' . basename($pattern)));
            }
            return $files;
        }

        /**
         * Sanitizes a filename against directory traversal attacks.
         * @param string $filename Filename to sanitize.
         * @return string Returns the sanitized filename.
         */
        public static function sanitizeFilename(string $filename){
            return trim(str_replace(['../', '..\\', './', '.\\'], '', urldecode($filename)), '/');
        }

        /**
         * Checks if a variable is empty.\
         * A numeric/bool safe version of PHP `empty()` function.
         * @var mixed $variable Variable to be checked.
         * @return bool Returns true if the variable is empty, false otherwise.
         */
        public static function isEmpty($variable){
            if(!isset($variable)) return true;
            if(is_string($variable)) return trim($variable) === '';
            if(is_numeric($variable) || is_bool($variable)) return false;
            if($variable instanceof Countable) return count($variable) === 0;
            return empty($variable);
        }

        /**
         * Returns the basename from a class (without its namespace).
         * @param string|object $class Classname or an object to get the class.
         * @return string Returns the class basename.
         */
        public static function classname($class){
            $class = is_object($class) ? get_class($class) : $class;
            return basename(str_replace('\\', '/', $class));
        }

    }
