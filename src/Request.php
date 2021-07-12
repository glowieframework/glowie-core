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
         * Returns the request clean URI (without query strings).
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
            return $_SERVER['REQUEST_METHOD'];
        }

        /**
         * Returns if the request was made using AJAX.
         * @return bool True if is AJAX or false if not.
         */
        public function isAJAX(){
            if(empty($_SERVER['HTTP_X_REQUESTED_WITH'])) return false;
            return $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
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