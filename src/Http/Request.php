<?php
    namespace Glowie\Core\Http;

    use Config;
    use Glowie\Core\Element;
    use Glowie\Core\Tools\Uploader;
    use Glowie\Core\Traits\ElementTrait;

    /**
     * Request handler for Glowie application.
     * @category Request
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) Glowie
     * @license MIT
     * @link https://glowie.tk
     */
    class Request{
        use ElementTrait;

        /**
         * Request headers.
         * @var string[]
         */
        private static $headers;

        /**
         * Request JSON data.
         * @var Element
         */
        private static $json;

        /**
         * Creates a new Request handler instance.
         */
        public function __construct(){
            self::$headers = array_change_key_case(getallheaders(), CASE_LOWER);
            self::$json = new Element(json_decode($this->getBody(), true) ?? []);

            // Request variables parsing
            switch(Config::get('other.request_vars', 'GET_POST')){
                case 'POST_GET':
                    $this->__constructTrait(array_merge($_POST, $_GET));
                    break;

                case 'REQUEST':
                    $this->__constructTrait($_REQUEST);
                    break;

                default:
                    $this->__constructTrait(array_merge($_GET, $_POST));
                    break;
            }
        }

        /**
         * Returns the request uploaded files.
         * @param string $input Valid file input field name.
         * @return Element[]|null Returns an array of the files, each one as an Element, or null if no files were uploaded.
         */
        public function getFiles(string $input){
            $uploader = new Uploader();
            if(empty($_FILES[$input])) return null;
            return $uploader->arrangeFiles($_FILES[$input], false);
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
            return trim(substr(trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'), strlen(APP_FOLDER)), '/');
        }

        /**
         * Returns the request IP address.
         * @return string Request IP address if valid or `0.0.0.0` if not.
         */
        public function getIPAddress(){
            return $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_FORWARDED'] ?? $_SERVER['HTTP_FORWARDED_FOR'] ?? $_SERVER['HTTP_FORWARDED_FOR'] ?? $_SERVER['HTTP_FORWARDED'] ?? $_SERVER['HTTP_CLUSTER_CLIENT_IP'] ?? $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }

        /**
         * Returns the raw request body as a string.
         * @return string Raw request body.
         */
        public function getBody(){
            return file_get_contents('php://input');
        }

        /**
         * Returns a JSON key from the request.
         * @param string|null $key (Optional) Key to get value (accepts dot notation keys). Leave empty to get the whole JSON Element.
         * @param mixed $default (Optional) Default value to return if the key does not exist.
         * @return mixed Returns the value if the key exists (or the default if not) or the JSON Element if a key is not provided.
         */
        public function getJson(?string $key = null, $default = null){
            if(empty($key)) return self::$json;
            return self::$json->get($key, $default);
        }

        /**
         * Returns the request method.
         * @return string Request method.
         */
        public function getMethod(){
            return $_SERVER['REQUEST_METHOD'] ?? 'GET';
        }

        /**
         * Returns **true** if the request was made using GET method.
         * @return bool Returns true or false matching the request method.
         */
        public function isGet(){
            return $this->getMethod() == 'GET';
        }

        /**
         * Returns **true** if the request was made using POST method.
         * @return bool Returns true or false matching the request method.
         */
        public function isPost(){
            return $this->getMethod() == 'POST';
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
         * @param mixed $default (Optional) Default value to return if the header does not exist.
         * @return mixed Returns the value if exists or the default if not.
         */
        public function getHeader(string $name, $default = null){
            return self::$headers[strtolower($name)] ?? $default;
        }

        /**
         * Gets all the request headers as an associative array.
         * @return string[] Returns an associative array with all headers.
         */
        public function getHeaders(){
            return getallheaders();
        }

        /**
         * Gets the `Content-Type` header.
         * @return string|null Returns the header value if exists or null if there is none.
         */
        public function getContentType(){
            return $this->getHeader('Content-Type');
        }

        /**
         * Gets the `Accept` header.
         * @return string|null Returns the header value if exists or null if there is none.
         */
        public function getAccept(){
            return $this->getHeader('Accept');
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
         * Returns if the request was made using a mobile device.\
         * **Note:** This information relies in the `User-Agent` header.
         * @return bool True if a mobile device identifier is present in the header, false otherwise or header is not present.
         */
        public function isMobile(){
            return preg_match("/(android|webos|avantgo|iphone|ipad|ipod|blackberry|iemobile|bolt|boost|cricket|docomo|fone|hiptop|mini|opera mini|kitkat|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $this->getHeader('User-Agent', ''));
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