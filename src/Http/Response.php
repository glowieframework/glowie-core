<?php
    namespace Glowie\Core\Http;

    use Util;
    use Config;
    use SimpleXMLElement;
    use Glowie\Core\Traits\ElementTrait;
    use Glowie\Core\View\Buffer;

    /**
     * Response handler for Glowie application.
     * @category Response
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) Glowie
     * @license MIT
     * @link https://glowie.tk
     */
    class Response{

        /**
         * HTTP 200 OK status code.
         * @var int
         */
        public const HTTP_OK = 200;

        /**
         * HTTP 201 Created status code.
         * @var int
         */
        public const HTTP_CREATED = 201;

        /**
         * HTTP 202 Accepted status code.
         * @var int
         */
        public const HTTP_ACCEPTED = 202;

        /**
         * HTTP 300 Moved Permanently status code.
         * @var int
         */
        public const HTTP_MOVED_PERMANENTLY = 301;

        /**
         * HTTP 302 Found status code.
         * @var int
         */
        public const HTTP_FOUND = 302;

        /**
         * HTTP 307 Temporary Redirect status code.
         * @var int
         */
        public const HTTP_TEMPORARY_REDIRECT = 307;

        /**
         * HTTP 308 Permanent Redirect status code.
         * @var int
         */
        public const HTTP_PERMANENT_REDIRECT = 308;

        /**
         * HTTP 400 Bad Request status code.
         * @var int
         */
        public const HTTP_BAD_REQUEST = 400;

        /**
         * HTTP 401 Unauthorized status code.
         * @var int
         */
        public const HTTP_UNAUTHORIZED = 401;

        /**
         * HTTP 403 Forbidden status code.
         * @var int
         */
        public const HTTP_FORBIDDEN = 403;

        /**
         * HTTP 404 Not Found status code.
         * @var int
         */
        public const HTTP_NOT_FOUND = 404;

        /**
         * HTTP 405 Method Not Alowed status code.
         * @var int
         */
        public const HTTP_METHOD_NOT_ALLOWED = 405;

        /**
         * HTTP 408 Request Timeout status code.
         * @var int
         */
        public const HTTP_REQUEST_TIMEOUT = 408;

        /**
         * HTTP 429 Too Many Requests status code.
         * @var int
         */
        public const HTTP_TOO_MANY_REQUESTS = 429;

        /**
         * HTTP 500 Internal Server Error status code.
         * @var int
         */
        public const HTTP_INTERNAL_SERVER_ERROR = 500;

        /**
         * HTTP 502 Bad Gateway status code.
         * @var int
         */
        public const HTTP_BAD_GATEWAY = 502;

        /**
         * HTTP 503 Service Unavailable status code.
         * @var int
         */
        public const HTTP_SERVICE_UNAVAILABLE = 503;

        /**
         * Content-Type header for HTML.
         * @var string
         */
        public const CONTENT_HTML = 'text/html';

        /**
         * Content-Type header for plain text.
         * @var string
         */
        public const CONTENT_PLAIN = 'text/plain';

        /**
         * Content-Type header for JSON.
         * @var string
         */
        public const CONTENT_JSON = 'application/json';

        /**
         * Content-Type header for XML.
         * @var string
         */
        public const CONTENT_XML = 'text/xml';

        /**
         * Applies Cross-Origin Resource Sharing (CORS) headers from your app configuration.
         */
        public function applyCors(){
            if(!Config::get('cors.enabled', true)) return;
            $this->setHeader('Access-Control-Allow-Methods', Config::get('cors.allowed_methods', ['*']));
            $this->setHeader('Access-Control-Allow-Origin', Config::get('cors.allowed_origins', ['*']));
            $this->setHeader('Access-Control-Allow-Headers', Config::get('cors.allowed_headers', ['*']));
            $this->setHeader('Access-Control-Max-Age', Config::get('cors.max_age', 0));
            if(!empty(Config::get('cors.exposed_headers', []))) $this->setHeader('Access-Control-Expose-Headers', Config::get('cors.exposed_headers', []));
            if(Config::get('cors.allow_credentials', false)) $this->setHeader('Access-Control-Allow-Credentials', 'true');
        }

        /**
         * Sets the HTTP status code for the response.
         * @param int $code HTTP status code to set.
         * @param string $message (Optional) Custom reason phrase to set.
         * @return Response Current Response instance for nested calls.
         */
        public function setStatusCode(int $code, string $message = ''){
            if(empty($message)){
                http_response_code($code);
            }else{
                header("HTTP/1.0 {$code} {$message}", true, $code);
            }
            return $this;
        }

        /**
         * Sets a **403 Forbidden** HTTP response code.
         * @return Response Current Response instance for nested calls.
         */
        public function deny(){
            return $this->setStatusCode(self::HTTP_FORBIDDEN);
        }

        /**
         * Sets a **404 Not Found** HTTP response code.
         * @return Response Current Response instance for nested calls.
         */
        public function notFound(){
            return $this->setStatusCode(self::HTTP_NOT_FOUND);
        }

        /**
         * Sets a **500 Internal Server Error** HTTP response code.
         * @return Response Current Response instance for nested calls.
         */
        public function fail(){
            return $this->setStatusCode(self::HTTP_INTERNAL_SERVER_ERROR);
        }

        /**
         * Sets a new header value or replaces the existing one.
         * @param string $name Header name to set.
         * @param string|array $value Header value to set. Can also be an array of values.
         * @return Response Current Response instance for nested calls.
         */
        public function setHeader(string $name, $value){
            $value = implode(', ', (array)$value);
            header("{$name}: {$value}");
            return $this;
        }

        /**
         * Sets a new header value or appends the value to the existing one.
         * @param string $name Header name to set.
         * @param string|array $value Header value to set.
         * @return Response Current Response instance for nested calls.
         */
        public function appendHeader(string $name, $value){
            $value = implode(', ', (array)$value);
            header("{$name}: {$value}", false);
            return $this;
        }

        /**
         * Sets a basic `Authorization` header with username and password.
         * @param string $username Username to set.
         * @param string $password Password to set.
         * @return Response Current Response instance for nested calls.
         */
        public function setAuthorization(string $username, string $password){
            return $this->setHeader('Authorization', 'Basic ' . base64_encode("{$username}:{$password}"));
        }

        /**
         * Sets a bearer `Authorization` header with a token.
         * @param string $token Token to set.
         * @return Response Current Response instance for nested calls.
         */
        public function setBearer(string $token){
            return $this->setHeader('Authorization', 'Bearer ' . $token);
        }

        /**
         * Sets the `Content-Type` header.
         * @param string $type Content-Type to set.
         * @return Response Current Response instance for nested calls.
         */
        public function setContentType(string $type){
            return $this->setHeader('Content-Type', $type);
        }

        /**
         * Forces the download of the content by setting the `Content-Disposition` header.
         * @param string $filename Filename to download including extension.
         * @return Response Current Response instance for nested calls.
         */
        public function setDownload(string $filename){
            return $this->setHeader('Content-Disposition', "filename=\"{$filename}\"");
        }

        /**
         * Disables the browser caching by setting the `Cache-Control` header.
         * @return Response Current Response instance for nested calls.
         */
        public function disableCache(){
            return $this->setHeader('Cache-Control', 'no-store, max-age=0, no-cache');
        }

        /**
         * Sends a raw plain text body to the response.
         * @param string $content Content to set as the body.
         */
        public function setBody(string $content){
            Buffer::clean();
            $this->setContentType(self::CONTENT_PLAIN);
            echo $content;
        }

        /**
         * Sends a JSON output to the response.
         * @param array|Element $data Associative array with data to encode to JSON. You can also use an Element.
         * @param int $flags (Optional) JSON encoding flags (same as in `json_encode()` function).
         * @param int $depth (Optional) JSON encoding maximum depth (same as in `json_encode()` function).
         */
        public function setJson($data, int $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK, int $depth = 512){
            Buffer::clean();
            $this->setContentType(self::CONTENT_JSON);
            if(Util::usesTrait($data, ElementTrait::class)) $data = $data->toArray();
            echo json_encode($data, $flags, $depth);
        }

        /**
         * Sends a XML output to the response.
         * @param array|Element $data Associative array with data to encode to XML. You can also use an Element.
         * @param string $root (Optional) Name of the XML root element.
         */
        public function setXML($data, string $root = 'data'){
            Buffer::clean();
            $this->setContentType(self::CONTENT_XML);
            $xml = new SimpleXMLElement("<?xml version=\"1.0\"?><{$root}></{$root}>");
            if(Util::usesTrait($data, ElementTrait::class)) $data = $data->toArray();
            $this->arrayToXML($data, $xml);
            echo $xml->asXML();
        }

         /**
         * Redirects to a relative or full URL.
         * @param string $destination Target URL to redirect to.
         * @param int $code (Optional) HTTP status code to pass with the redirect.
         * @return void
         */
        public function redirect(string $destination, int $code = self::HTTP_TEMPORARY_REDIRECT){
            Buffer::clean();
            $this->setStatusCode($code);
            header('Location: ' . $destination, true, $code);
            die();
        }

         /**
         * Redirects to an URL relative to the application path.
         * @param string $path (Optional) Path to append to the base URL.
         * @param int $code (Optional) HTTP status code to pass with the redirect.
         */
        public function redirectBase(string $path = '', int $code = self::HTTP_TEMPORARY_REDIRECT){
            return $this->redirect(Util::baseUrl($path), $code);
        }

        /**
         * Redirects to a named route.
         * @param string $route Route name.
         * @param array $params (Optional) Route parameters to bind into the URL.
         * @param int $code (Optional) HTTP status code to pass with the redirect.
         */
        public function redirectRoute(string $route, array $params = [], int $code = self::HTTP_TEMPORARY_REDIRECT){
            return $this->redirect(Util::route($route, $params), $code);
        }

        /**
         * Converts an array to XML.
         * @param array $data Array with data to convert to XML.
         * @param SimpleXMLElement $xml_data XML Element to append data.
         */
        private function arrayToXML(array $data, &$xml_data){
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    if (is_numeric($key)) $key = 'item' . $key;
                    $subnode = $xml_data->addChild($key);
                    $this->arrayToXML($value, $subnode);
                } else {
                    $xml_data->addChild("$key", htmlspecialchars("$value"));
                }
            }
        }

    }

?>