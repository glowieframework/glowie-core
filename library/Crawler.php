<?php

    /**
     * HTTP client for Glowie application.
     * @category HTTP client
     * @package glowie
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 0.2-alpha
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
            return $this->fetch($url, 'GET', [], $headers, $timeout);
        }

        /**
         * Performs a POST request.
         * @param string $url URL to perform request.
         * @param array $data (Optional) POST data to send in the request. Must be an associative array with the corresponding field names and values.
         * @param array $headers (Optional) Custom headers to send in the request. Must be an associative array with the key being the name of the header\
         * and the value the header value (can be a string or an array of strings).
         * @param int $timeout (Optional) Maximum number of seconds that this request can wait for a response. Default is 30 seconds. Use 0 for unlimited.
         * @return string|bool Returns the response as a string on success (HTTP 200 status code) or false on errors.
         */
        public function post(string $url, array $data = [], array $headers = [], int $timeout = 30){
            return $this->fetch($url, 'POST', $data, $headers, $timeout);
        }

        /**
         * Fetches a request using cURL.
         * @param string $method (Optional) Method to use in the request. Default is GET.
         * @param string $url URL to perform request.
         * @param array $data (Optional) POST data to send in the request. Must be an associative array with the corresponding field names and values.
         * @param array $headers (Optional) Custom headers to send in the request. Must be an associative array with the key being the name of the header\
         * and the value the header value (can be a string or an array of strings).
         * @param int $timeout (Optional) Maximum number of seconds that this request can wait for a response. Default is 30 seconds. Use 0 for unlimited.
         * @return string|bool Returns the response as a string on success (HTTP 200 status code) or false on errors.
         */
        private function fetch(string $url, string $method = 'GET', array $data = [], array $headers = [], int $timeout = 30){
            // Initializes cURL
            $curl = curl_init($url);

            // Sets cURL options
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);

            // Sets options on POST method
            if ($method == 'POST') {
                curl_setopt($curl, CURLOPT_POST, 1);
                if (!empty($data)) curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }

            // Sets headers
            if (!empty($headers)) {
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