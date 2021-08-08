<?php
    namespace Glowie\Core\Http;

    use Util;
    use Glowie\Core\Element;
    use Exception;

    /**
     * Request handler for Glowie application.
     * @category Request
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.0
     */
    class Request{

        /**
         * Current Request headers.
         * @var array
         */
        private static $headers;

        /**
         * Creates a new Request handler instance.
         */
        public function __construct(){
            $headers = getallheaders();
            if(!$headers) throw new Exception('Request: Error retrieving request headers');
            self::$headers = array_change_key_case($headers, CASE_LOWER);
        }

        /**
         * Returns the request full URL.
         * @return string Request full URL.
         */
        public function getURL(){
            return (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        }

        /**
         * Returns the request clean URI (without hostname or query strings).
         * @return string Request clean URI.
         */
        public function getURI(){
            return trim(substr(trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'), strlen(GLOWIE_APP_FOLDER)), '/');
        }

        /**
         * Returns the request IP address.
         * @return string Request IP address if valid or `0.0.0.0` if not.
         */
        public function getIPAddress(){
            if(!empty($_SERVER['HTTP_CLIENT_IP'])){
                return $_SERVER['HTTP_CLIENT_IP'];
            }else if(!empty($_SERVER['HTTP_X_CLIENT_IP'])){
                return $_SERVER['HTTP_X_CLIENT_IP'];
            }else if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
                return $_SERVER['HTTP_X_FORWARDED_FOR'];
            }else if(!empty($_SERVER['HTTP_X_FORWARDED'])){
                return $_SERVER['HTTP_X_FORWARDED'];
            }else if(!empty($_SERVER['HTTP_FORWARDED_FOR'])){
                return $_SERVER['HTTP_FORWARDED_FOR'];
            }else if(!empty($_SERVER['HTTP_FORWARDED'])){
                return $_SERVER['HTTP_FORWARDED'];
            }else if(!empty($_SERVER['HTTP_CLUSTER_CLIENT_IP'])){
                return $_SERVER['HTTP_CLUSTER_CLIENT_IP'];
            }else if(!empty($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'])){
                return $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
            }else if(!empty($_SERVER['REMOTE_ADDR'])){
                return $_SERVER['REMOTE_ADDR'];
            }else{
                return '0.0.0.0';
            }
        }

        /**
         * Returns the raw request body as a string.
         * @return string Raw request body.
         */
        public function getBody(){
            return file_get_contents('php://input');
        }

        /**
         * Returns the request JSON data as an object.
         * @return Element|null The object containing the JSON data if valid or null if not.
         */
        public function getJson(){
            $json = json_decode($this->getBody(), true);
            if(!$json) return null;
            return new Element($json);
        }

        /**
         * Returns the request method.
         * @return string Request method.
         */
        public function getMethod(){
            return $_SERVER['REQUEST_METHOD'] ?? 'GET';
        }

        /**
         * Returns the previous URL where the user was.\
         * **Note:** This information relies in the `Referer` header.
         * @return string|null Returns the URL if the header exists or null if not.
         */
        public function getPreviousUrl(){
            return $this->getHeader('Referer');
        }

        /**
         * Gets the value of a header.
         * @param string $name Header name to get.
         * @return string|null Returns the header value if exists or null if there is none.
         */
        public function getHeader(string $name){
            return self::$headers[strtolower($name)] ?? null;
        }

        /**
         * Gets a basic Authorization header.
         * @return Element|null Returns an object with the username and password if exists or null if there is none.
         */
        public function getAuthorization(){
            $value = $this->getHeader('Authorization');
            if(!$value) return null;
            if(!Util::startsWith($value, 'Basic ')) return null;
            $value = base64_decode(substr($value, 6));
            if(!$value) return null;
            $value = explode(':', $value, 2);
            return new Element(['username' => $value[0] ?? null, 'password' => $value[1] ?? null]);
        }

        /**
         * Gets the Content-Type header.
         * @return string|null Returns the header value if exists or null if there is none.
         */
        public function getContentType(){
            return $this->getHeader('Content-Type');
        }

        /**
         * Gets the value of a variable from the request.
         * @param string $key Variable key to get.
         * @param mixed $default (Optional) Default value to return if the key does not exists.
         * @return mixed Returns the value if exists or the default if not.
         */
        public function getVar(string $key, $default = null){
            return $_REQUEST[$key] ?? $default;
        }

        /**
         * Returns if the request was made using AJAX.\
         * **Note:** This information relies in the `X-Requested-With` header.
         * @return bool True if is AJAX or false if not or header is not present.
         */
        public function isAjax(){
            return $this->getHeader('X-Requested-With') == 'XMLHttpRequest';
        }

        /**
         * Returns if the request was made using a secure connection (HTTPS).
         * @return bool True if secure or false if not.
         */
        public function isSecure(){
            return isset($_SERVER['HTTPS']);
        }

        /**
         * Compares an input value against the session CSRF token if exists.
         * @param string $input Input value to compare.
         * @return bool Returns true if the tokens match or false if not.
         */
        public function checkCsrfToken(string $input){
            $session = new Session();
            if(!$session->has('CSRF_TOKEN')) return false;
            return hash_equals($session->get('CSRF_TOKEN'), $input);
        }

    }

?>