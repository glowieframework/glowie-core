<?php

    /**
     * Miscellaneous utilities for Glowie application.
     * @category Utility
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 0.2-alpha
     */
    class Util{

        /**
         * Logs a variable in human-readable way.
         * @param mixed $var Variable to be printed.
         * @param bool $exit (Optional) Stop code execution after logging.
         */
        public static function log($var, $exit = false){
            echo '<pre>';
            print_r($var);
            echo '</pre>';
            if($exit) exit();
        }

        /**
         * Returns the full URL relative to the application path.
         * @param string $path (Optional) Path to append to the base URL.
         * @return string Full base URL.
         */
        public static function baseUrl(string $path = ''){
            !empty($_SERVER['HTTPS']) ? $protocol = 'https://' : $protocol = 'http://';
            $appFolder = $GLOBALS['glowieConfig']['app_folder'];
            if(!self::startsWith($appFolder, '/')) $appFolder = '/' . $appFolder;
            if(!self::endsWith($appFolder, '/')) $appFolder = $appFolder . '/';
            if(self::startsWith($path, '/')) $path = substr($path, 1);
            if(self::endsWith($path, '/')) $path = substr($path, 0, -1);
            return $protocol . $_SERVER['HTTP_HOST'] . $appFolder . $path;
        }

        /**
         * Redirects to a relative or full URL.
         * @param string $destination Target URL to redirect to.
         * @param bool $js (Optional) Redirect using JavaScript (when inside modals or iframes).
         */
        public static function redirect(string $destination, bool $js = false){
            if ($js) {
                echo '<script>window.location = "' . $destination . '"</script>';
            } else {
                header('Location: ' . $destination);
            }
        }

        /**
         * Reorders a multidimensional array by a specific key value.
         * @param array $array Array to reorder.
         * @param string $key Key to use while ordering.
         * @param int $order (Optional) Ordering type, can be **SORT_ASC** (ascending) or **SORT_DESC** (descending).
         * @return array Returns a new sorted array.
         */
        public static function orderArray(array $array, string $key, int $order = SORT_ASC){
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
         * Filters a multidimensional array by a specific key value.
         * @param array $array Array to filder.
         * @param string $key Key to use while filtering.
         * @param mixed $value Value to filter.
         * @return array Returns a new filtered array.
         */
        public static function filterArray(array $array, string $key, $value){
            $newarray = array();
            if (is_array($array) && count($array) > 0) {
                foreach (array_keys($array) as $col) {
                    $temp[$col] = $array[$col][$key];
                    if ($temp[$col] == $value) $newarray[$col] = $array[$col];
                }
            }
            return $newarray;
        }

        public static function searchArray($array, $key, $value){
            $result = null;
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
        public static function startsWith($haystack, $needle){
            $length = strlen($needle);
            return substr($haystack, 0, $length) == $needle;
        }

        /**
         * Checks if a string ends with a given substring.
         * @param string $haystack The string to search in.
         * @param string $needle The substring to search for in the haystack.
         * @return bool Returns **true** if haystack ends with needle, **false** otherwise.
         */
        public static function endsWith($haystack, $needle){
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
            $key = hash('sha256', $GLOBALS['glowieConfig']['api_key']);
            $iv = substr(hash('sha256', $GLOBALS['glowieConfig']['api_token']), 0, 16);
            $string = base64_encode(openssl_encrypt($string, "AES-256-CBC", $key, 0, $iv));
            return $string;
        }

        /**
         * Decrypts a string using your application secret keys.
         * @param string $string String to decrypt.
         * @return string Returns the decrypted string.
         */
        public static function decryptString(string $string){
            $key = hash('sha256', $GLOBALS['glowieConfig']['api_key']);
            $iv = substr(hash('sha256', $GLOBALS['glowieConfig']['api_token']), 0, 16);
            $string = openssl_decrypt(base64_decode($string), "AES-256-CBC", $key, 0, $iv);
            return $string;
        }

    }

?>