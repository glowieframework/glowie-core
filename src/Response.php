<?php
    namespace Glowie\Core;

    use Util;

    /**
     * Response handler for Glowie application.
     * @category Response
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.0
     */
    class Response{
        
        public const HTTP_OK = 200;
        public const HTTP_BAD_REQUEST = 400;
        public const HTTP_UNAUTHORIZED = 401;
        public const HTTP_FORBIDDEN = 403;
        public const HTTP_NOT_FOUND = 404;
        public const HTTP_METHOD_NOT_ALLOWED = 405;

        public const CONTENT_HTML = 'text/html';
        public const CONTENT_TEXT = 'text/plain';
        public const CONTENT_JSON = 'application/json';

        public function setStatusCode(int $code){
            http_response_code($code);
        }

        public function setHeader(string $name, string $value){
            header("{$name}: {$value}");
        }

        public function setContentType(string $type){
            header("Content-type: {$type}");
        }
        
         /**
         * Redirects to a relative or full URL.
         * @param string $destination Target URL to redirect to.
         * @param int $code (Optional) HTTP response code to pass with the redirect.
         * @return void
         */
        public function redirect(string $destination, int $code = 302){
            header('Location: ' . $destination, true, $code);
            die();
        }

         /**
         * Redirects to an URL relative to the application path.
         * @param string $path (Optional) Path to append to the base URL.
         * @param int $code (Optional) HTTP response code to pass with the redirect.
         */
        public function redirectBase(string $path = '', int $code = 302){
            return $this->redirect(Util::baseUrl($path), $code);
        }

        /**
         * Redirects to a named route.
         * @param string $route Route internal name/identifier.
         * @param array $params (Optional) Route parameters to bind into the URL.
         * @param int $code (Optional) HTTP response code to pass with the redirect.
         */
        public function redirectRoute(string $route, array $params = [], int $code = 302){
            return $this->redirect(Util::route($route, $params), $code);
        }

    }

?>