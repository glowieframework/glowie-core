<?php
    namespace Glowie\Core;

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
         * Performs a GET request.
         * @param string $url URL to perform request.
         * @param array $headers (Optional) Custom headers to send in the request. Must be an associative array with the key being the name of the header\
         * and the value the header value (can be a string or an array of strings).
         * @param int $timeout (Optional) Maximum number of seconds that this request can wait for a response. Default is 30 seconds. Use 0 for unlimited.
         * @return string|bool Returns the response as a string on success (HTTP 200 status code) or false on errors.
         */
        public function get(string $url, array $headers = [], int $timeout = 30){
            return $this->request($url, 'GET', [], $headers, $timeout);
        }

        /**
         * Performs a POST request.
         * @param string $url URL to perform request.
         * @param array|string $data (Optional) Data to send in the request. Can be an associative array with the corresponding field names and values, JSON\
         * or plain text. **Content-Type** header must be needed depending on chosen data type.
         * @param array $headers (Optional) Custom headers to send in the request. Must be an associative array with the key being the name of the header\
         * and the value the header value (can be a string or an array of strings).
         * @param int $timeout (Optional) Maximum number of seconds that this request can wait for a response. Default is 30 seconds. Use 0 for unlimited.
         * @return string|bool Returns the response as a string on success (HTTP 200 status code) or false on errors.
         */
        public function post(string $url, $data = [], array $headers = [], int $timeout = 30){
            return $this->request($url, 'POST', $data, $headers, $timeout);
        }

        /**
         * Performs a PUT request.
         * @param string $url URL to perform request.
         * @param array|string $data (Optional) Data to send in the request. Can be an associative array with the corresponding field names and values, JSON\
         * or plain text. **Content-Type** header must be needed depending on chosen data type.
         * @param array $headers (Optional) Custom headers to send in the request. Must be an associative array with the key being the name of the header\
         * and the value the header value (can be a string or an array of strings).
         * @param int $timeout (Optional) Maximum number of seconds that this request can wait for a response. Default is 30 seconds. Use 0 for unlimited.
         * @return string|bool Returns the response as a string on success (HTTP 200 status code) or false on errors.
         */
        public function put(string $url, $data = [], array $headers = [], int $timeout = 30){
            return $this->request($url, 'PUT', $data, $headers, $timeout);
        }

        /**
         * Performs a PATCH request.
         * @param string $url URL to perform request.
         * @param array|string $data (Optional) Data to send in the request. Can be an associative array with the corresponding field names and values, JSON\
         * or plain text. **Content-Type** header must be needed depending on chosen data type.
         * @param array $headers (Optional) Custom headers to send in the request. Must be an associative array with the key being the name of the header\
         * and the value the header value (can be a string or an array of strings).
         * @param int $timeout (Optional) Maximum number of seconds that this request can wait for a response. Default is 30 seconds. Use 0 for unlimited.
         * @return string|bool Returns the response as a string on success (HTTP 200 status code) or false on errors.
         */
        public function patch(string $url, $data = [], array $headers = [], int $timeout = 30){
            return $this->request($url, 'PATCH', $data, $headers, $timeout);
        }

        /**
         * Performs a DELETE request.
         * @param string $url URL to perform request.
         * @param array|string $data (Optional) Data to send in the request. Can be an associative array with the corresponding field names and values, JSON\
         * or plain text. **Content-Type** header must be needed depending on chosen data type.
         * @param array $headers (Optional) Custom headers to send in the request. Must be an associative array with the key being the name of the header\
         * and the value the header value (can be a string or an array of strings).
         * @param int $timeout (Optional) Maximum number of seconds that this request can wait for a response. Default is 30 seconds. Use 0 for unlimited.
         * @return string|bool Returns the response as a string on success (HTTP 200 status code) or false on errors.
         */
        public function delete(string $url, $data = [], array $headers = [], int $timeout = 30){
            return $this->request($url, 'DELETE', $data, $headers, $timeout);
        }

        /**
         * Performs a HTTP request.
         * @param string $url URL to perform request.
         * @param string $method (Optional) Method to use in the request. Default is GET.
         * @param array|string $data (Optional) Data to send in the request. Can be an associative array with the corresponding field names and values, JSON\
         * or plain text. **Content-Type** header must be needed depending on chosen data type.
         * @param array $headers (Optional) Custom headers to send in the request. Must be an associative array with the key being the name of the header\
         * and the value the header value (can be a string or an array of strings).
         * @param int $timeout (Optional) Maximum number of seconds that this request can wait for a response. Default is 30 seconds. Use 0 for unlimited.
         * @return string|bool Returns the response as a string on success (HTTP 200 status code) or false on errors.
         */
        public function request(string $url, string $method = 'GET', $data = [], array $headers = [], int $timeout = 30){
            // Initializes cURL
            $curl = curl_init($url);

            // Sets cURL options
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);

            // Sets headers
            if (!empty($headers)) {
                if(!is_array($headers)) trigger_error('Crawler: $headers must be an array', E_USER_ERROR);
                $parsed = [];
                foreach ($headers as $key => $value) {
                    if(is_array($value)){
                        $parsed[] = $key . ': ' . implode(', ', $value);
                    }else{
                        $parsed[] = $key . ': ' . $value;
                    }
                }
                curl_setopt($curl, CURLOPT_HTTPHEADER, $parsed);
            }
            
            // Sets method
            $method = strtoupper(trim($method));
            if($method == 'GET'){
                # default
            }else if ($method == 'POST') {
                curl_setopt($curl, CURLOPT_POST, 1);
            }else if($method == 'PUT'){
                curl_setopt($curl, CURLOPT_PUT, 1);
            }else{
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            }

            // Sets data
            if ($method !== 'GET' && !empty($data)) curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

            // Fetches the request
            $response = curl_exec($curl);
            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            // Closes current connection
            curl_close($curl);

            // Returns result on HTTP 200 status code
            if ($status === 200) {
                return $response;
            } else {
                return false;
            }
        }

    }

?>