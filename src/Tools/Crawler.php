<?php
    namespace Glowie\Core\Tools;

    use Glowie\Core\Http\Response;

    /**
     * HTTP client for Glowie application.
     * @category HTTP client
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.0
     */
    class Crawler{

        /**
         * Custom request headers.
         * @var array
         */
        private $headers = [];

        /**
         * Creates a new HTTP client instance.
         * @param array $headers (Optional) Custom headers to send in the request. Must be an associative array with the key being the name of the header\
         * and the value the header value (can be a string or an array of strings).
         * @param int $timeout (Optional) Maximum number of seconds that the request can wait for a response. Default is 30 seconds. Use 0 for unlimited.
         */
        public function __construct(array $headers = [], int $timeout = 30){
            // Set headers
            if(!empty($headers)){
                foreach($headers as $key => $value) $this->addHeader($key, $value);
            }

            // Set timeout
            $this->timeout = $timeout;
        }

        /**
         * Adds a custom header to the request.
         * @param string $name Header name.
         * @param string|array $content Header content. Can be a value or an array of values.
         */
        public function addHeader(string $name, $content){
            $content = implode(', ', (array)$content);
            $this->headers[] = "{$name}: {$content}";
        }

        /**
         * Sets the maximum number of seconds that the request can wait for a response.
         * @param int $timeout Timeout in seconds. Use 0 for unlimited.
         */
        public function setTimeout(int $timeout){
            $this->timeout = $timeout;
        }

        /**
         * Performs a GET request.
         * @param string $url URL to perform request.
         * @return string|bool Returns the response as a string on success (HTTP 200 status code) or false on failure.
         */
        public function get(string $url){
            return $this->request($url, 'GET');
        }

        /**
         * Performs a POST request.
         * @param string $url URL to perform request.
         * @param array|string $data (Optional) Data to send in the request. Can be an associative array with the corresponding field names and values, JSON\
         * or plain text. **Content-Type** header must be needed depending on chosen data type.
         * @return string|bool Returns the response as a string on success (HTTP 200 status code) or false on failure.
         */
        public function post(string $url, $data = []){
            return $this->request($url, 'POST', $data);
        }

        /**
         * Performs a PUT request.
         * @param string $url URL to perform request.
         * @param array|string $data (Optional) Data to send in the request. Can be an associative array with the corresponding field names and values, JSON\
         * or plain text. **Content-Type** header must be needed depending on chosen data type.
         * @return string|bool Returns the response as a string on success (HTTP 200 status code) or false on failure.
         */
        public function put(string $url, $data = []){
            return $this->request($url, 'PUT', $data);
        }

        /**
         * Performs a PATCH request.
         * @param string $url URL to perform request.
         * @param array|string $data (Optional) Data to send in the request. Can be an associative array with the corresponding field names and values, JSON\
         * or plain text. **Content-Type** header must be needed depending on chosen data type.
         * @return string|bool Returns the response as a string on success (HTTP 200 status code) or false on failure.
         */
        public function patch(string $url, $data = []){
            return $this->request($url, 'PATCH', $data);
        }

        /**
         * Performs a DELETE request.
         * @param string $url URL to perform request.
         * @param array|string $data (Optional) Data to send in the request. Can be an associative array with the corresponding field names and values, JSON\
         * or plain text. **Content-Type** header must be needed depending on chosen data type.
         * @return string|bool Returns the response as a string on success (HTTP 200 status code) or false on failure.
         */
        public function delete(string $url, $data = []){
            return $this->request($url, 'DELETE', $data);
        }

        /**
         * Performs an HTTP request.
         * @param string $url URL to perform request.
         * @param string $method (Optional) Method to use in the request. Default is GET.
         * @param array|string $data (Optional) Data to send in the request. Can be an associative array with the corresponding field names and values, JSON\
         * or plain text. **Content-Type** header must be needed depending on chosen data type.
         * @return string|bool Returns the response as a string on success (HTTP 200 status code) or false on failure.
         */
        public function request(string $url, string $method = 'GET', $data = []){
            // Initializes cURL
            $curl = curl_init($url);

            // Sets cURL options
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->timeout);

            // Sets headers
            if (!empty($this->headers)) curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);

            // Sets method
            $method = strtoupper(trim($method));
            switch($method){
                case 'GET':
                    break;
                case 'POST':
                    curl_setopt($curl, CURLOPT_POST, 1);
                    break;
                case 'PUT':
                    curl_setopt($curl, CURLOPT_PUT, 1);
                    break;
                default:
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
                    break;
            }

            // Sets data
            if ($method !== 'GET' && !empty($data)) curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

            // Fetches the request
            $response = curl_exec($curl);
            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            // Closes current connection
            curl_close($curl);

            // Returns result on HTTP 200 status code
            if ($status === Response::HTTP_OK) {
                return $response;
            } else {
                return false;
            }
        }

    }

?>