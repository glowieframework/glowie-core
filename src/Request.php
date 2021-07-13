<?php
    namespace Glowie\Core;

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
         * Returns the request IP address.\
         * **Note:** If the IP address is not valid or cannot be detected, returns `0.0.0.0`.
         * @return string Request IP address.
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
         * Returns the request method.
         * @return string Request method.
         */
        public function getMethod(){
            return $_SERVER['REQUEST_METHOD'] ?? 'GET';
        }

        /**
         * Returns the previous URL where the user was.\
         * **Note:** This information relies in the `HTTP_REFERER` header. This header cannot be sent\
         * from some browsers or be unavailable when using mismatching HTTP protocols.
         */
        public function getPreviousUrl(){
            return $_SERVER['HTTP_REFERER'] ?? '';
        }

        /**
         * Gets the value of a header.
         * @param string $name Header name to get.
         * @return string|null Returns the header value if exists or null if there is none.
         */
        public function getHeader(string $name){
            $headers = getallheaders();
            if(!$headers) trigger_error('Request: Error retrieving request headers', E_USER_ERROR);
            return $headers[$name] ?? null;
        }

        /**
         * Gets the value of a variable from the request.
         * @param string $key Variable key to get.
         * @return mixed Returns the value if exists or null if not.
         */
        public function getVar(string $key){
            return $_REQUEST[$key] ?? null;
        }

        /**
         * Returns if the request was made using AJAX.\
         * **Note:** This information relies in the `X-Requested-With` header. This header cannot be sent\
         * from some Javascript frameworks.
         * @return bool True if is AJAX or false if not.
         */
        public function isAjax(){
            if(empty($_SERVER['HTTP_X_REQUESTED_WITH'])) return false;
            return $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
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