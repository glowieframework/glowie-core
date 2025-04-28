<?php

namespace Glowie\Core\Tools;

use Glowie\Core\Element;
use Glowie\Core\Exception\RequestException;
use Exception;

/**
 * HTTP client for Glowie application.
 * @category HTTP client
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 * @see https://glowie.gabrielsilva.dev.br/docs/latest/extra/http-requests
 */
class Crawler
{

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
     * Content-Type header for form data.
     * @var string
     */
    public const CONTENT_FORM = 'application/x-www-form-urlencoded';

    /**
     * Content-Type header for multipart form data.
     * @var string
     */
    public const CONTENT_MULTIPART = 'multipart/form-data';

    /**
     * Current content type.
     * @var string
     */
    private $type = self::CONTENT_MULTIPART;

    /**
     * Custom request headers.
     * @var array
     */
    private $headers = [];

    /**
     * Custom request cookies.
     * @var array
     */
    private $cookies = [];

    /**
     * Throw exception on failure.
     * @var bool
     */
    private $throw = false;

    /**
     * Verify SSL certificate.
     * @var bool
     */
    private $verify = true;

    /**
     * Enable redirects.
     * @var bool
     */
    private $redirect = true;

    /**
     * Request maximum time.
     * @var int
     */
    private $timeout = 30;

    /**
     * Creates a new HTTP client instance.
     * @param array $headers (Optional) Custom headers to send in the request. Must be an associative array with the key being the name of the header\
     * and the value the header value (can be a string or an array of strings).
     */
    public function __construct(array $headers = [])
    {
        if (!extension_loaded('curl')) throw new Exception('Crawler: Missing "curl" extension in your PHP installation');
        foreach ($headers as $key => $value) $this->addHeader($key, $value);
    }

    /**
     * Creates a new HTTP client instance in a static-binding.
     * @return Crawler New instance of the client.
     */
    public static function make()
    {
        return new self;
    }

    /**
     * Adds a custom header to the request.
     * @param string $name Header name.
     * @param string|array $content Header content. Can be a value or an array of values.
     * @return Crawler Current Crawler instance for nested calls.
     */
    public function addHeader(string $name, $content)
    {
        $content = implode(', ', (array)$content);
        $this->headers[] = "{$name}: {$content}";
        return $this;
    }

    /**
     * Clears all headers from the request.
     * @return Crawler Current Crawler instance for nested calls.
     */
    public function clearHeaders()
    {
        $this->headers = [];
        return $this;
    }

    /**
     * Adds a custom cookie to the request.
     * @param string $name Cookie name.
     * @param string $value Cookie value.
     * @return Crawler Current Crawler instance for nested calls.
     */
    public function addCookie(string $name, string $value)
    {
        $this->cookies[] = "{$name}={$value}";
        return $this;
    }

    /**
     * Clears all cookies from the request.
     * @return Crawler Current Crawler instance for nested calls.
     */
    public function clearCookies()
    {
        $this->cookies = [];
        return $this;
    }

    /**
     * Sets the `Content-Type` header.
     * @param string $type Content-Type to set.
     * @return Crawler Current Crawler instance for nested calls.
     */
    public function setContentType(string $type)
    {
        $this->type = $type;
        $this->addHeader('Content-Type', $type);
        return $this;
    }

    /**
     * Sets the `Accept` header.
     * @param string $type Accepted type to set.
     * @return Crawler Current Crawler instance for nested calls.
     */
    public function setAccept(string $type)
    {
        $this->addHeader('Accept', $type);
        return $this;
    }

    /**
     * Sets the `Accept` header to JSON format.
     * @return Crawler Current Crawler instance for nested calls.
     */
    public function acceptJson()
    {
        return $this->setAccept(self::CONTENT_JSON);
    }

    /**
     * Sets the `Content-Type` header to JSON format.
     * @return Crawler Current Crawler instance for nested calls.
     */
    public function asJson()
    {
        return $this->setContentType(self::CONTENT_JSON);
    }

    /**
     * Sets the `Content-Type` header to form format.
     * @return Crawler Current Crawler instance for nested calls.
     */
    public function asForm()
    {
        return $this->setContentType(self::CONTENT_FORM);
    }

    /**
     * Sets a basic `Authorization` header with username and password.
     * @param string $username Username to set.
     * @param string $password Password to set.
     * @return Crawler Current Crawler instance for nested calls.
     */
    public function setAuthorization(string $username, string $password)
    {
        $this->addHeader('Authorization', 'Basic ' . base64_encode("{$username}:{$password}"));
        return $this;
    }

    /**
     * Sets a bearer `Authorization` header with a token.
     * @param string $token Token to set.
     * @return Crawler Current Crawler instance for nested calls.
     */
    public function setBearer(string $token)
    {
        $this->addHeader('Authorization', 'Bearer ' . $token);
        return $this;
    }

    /**
     * Sets the maximum number of seconds that the request can wait for a response.
     * @param int $timeout Timeout in seconds. Use 0 for unlimited.
     * @return Crawler Current Crawler instance for nested calls.
     */
    public function setTimeout(int $timeout)
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * Throws an exception if the status code is greater than 400.
     * @param bool $option (Optional) Set to **true** to throw on error, **false** otherwise.
     * @return Crawler Current Crawler instance for nested calls.
     */
    public function throwOnError(bool $option = true)
    {
        $this->throw = $option;
        return $this;
    }

    /**
     * Disables the SSL certificate verification in the request.
     * @param bool $option (Optional) Set to **true** to bypass the verification, **false** otherwise.
     * @return Crawler Current Crawler instance for nested calls.
     */
    public function bypassVerification(bool $option = true)
    {
        $this->verify = !$option;
        return $this;
    }

    /**
     * Disables redirects in the request.
     * @param bool $option (Optional) Set to **true** to disable redirects, **false** otherwise.
     * @return Crawler Current Crawler instance for nested calls.
     */
    public function noRedirects(bool $option = true)
    {
        $this->redirect = !$option;
        return $this;
    }

    /**
     * Performs a GET request.
     * @param string $url URL to perform request.
     * @param string|array $data (Optional) Data to send in the request as plain text or an associative array.
     * @return Element|bool Returns the response as an Element on success or false on failure.
     */
    public function get(string $url, $data = '')
    {
        return $this->request($url, 'GET', $data);
    }

    /**
     * Performs a POST request.
     * @param string $url URL to perform request.
     * @param string|array $data (Optional) Data to send in the request as plain text or an associative array.
     * @return Element|bool Returns the response as an Element on success or false on failure.
     */
    public function post(string $url, $data = '')
    {
        return $this->request($url, 'POST', $data);
    }

    /**
     * Performs a PUT request.
     * @param string $url URL to perform request.
     * @param string|array $data (Optional) Data to send in the request as plain text or an associative array.
     * @return Element|bool Returns the response as an Element on success or false on failure.
     */
    public function put(string $url, $data = '')
    {
        return $this->request($url, 'PUT', $data);
    }

    /**
     * Performs a PATCH request.
     * @param string $url URL to perform request.
     * @param string|array $data (Optional) Data to send in the request as plain text or an associative array.
     * @return Element|bool Returns the response as an Element on success or false on failure.
     */
    public function patch(string $url, $data = '')
    {
        return $this->request($url, 'PATCH', $data);
    }

    /**
     * Performs a DELETE request.
     * @param string $url URL to perform request.
     * @param string|array $data (Optional) Data to send in the request as plain text or an associative array.
     * @return Element|bool Returns the response as an Element on success or false on failure.
     */
    public function delete(string $url, $data = '')
    {
        return $this->request($url, 'DELETE', $data);
    }

    /**
     * Performs a HEAD request.
     * @param string $url URL to perform request.
     * @param string|array $data (Optional) Data to send in the request as plain text or an associative array.
     * @return Element|bool Returns the response as an Element on success or false on failure.
     */
    public function head(string $url, $data = '')
    {
        return $this->request($url, 'HEAD', $data);
    }

    /**
     * Performs an OPTIONS request.
     * @param string $url URL to perform request.
     * @param string|array $data (Optional) Data to send in the request as plain text or an associative array.
     * @return Element|bool Returns the response as an Element on success or false on failure.
     */
    public function options(string $url, $data = '')
    {
        return $this->request($url, 'OPTIONS', $data);
    }

    /**
     * Performs an HTTP request.
     * @param string $url URL to perform request.
     * @param string $method (Optional) Method to use in the request. Default is `GET`.
     * @param string|array $data (Optional) Data to send in the request as plain text or an associative array.
     * @return Element|bool Returns the response as an Element on success or false on failure.
     * @throws Exception Throws an exception if the status code is greater than 400 and `throwOnError()` is set to **true**.
     */
    public function request(string $url, string $method = 'GET', $data = '')
    {
        // Initializes cURL
        $curl = curl_init();

        // Sets cURL options
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_AUTOREFERER => $this->redirect,
            CURLOPT_FOLLOWLOCATION => $this->redirect,
            CURLOPT_SSL_VERIFYPEER => $this->verify,
            CURLOPT_SSL_VERIFYSTATUS => $this->verify,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
        ]);

        // Sets headers
        if (!empty($this->headers)) curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);

        // Sets cookies
        if (!empty($this->cookies)) curl_setopt($curl, CURLOPT_COOKIE, implode('; ', $this->cookies));

        // Sets method
        $method = strtoupper(trim($method));
        switch ($method) {
            case 'GET':
                break;
            case 'POST':
                curl_setopt($curl, CURLOPT_POST, true);
                break;
            case 'PUT':
                curl_setopt($curl, CURLOPT_PUT, true);
                break;
            default:
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
                break;
        }

        // Sets the data
        if (!empty($data)) {
            if ($method === 'GET') {
                if (is_array($data)) {
                    $url = $url . '?' . http_build_query($data);
                } else {
                    $url = $url . '?' . $data;
                }
            } else {
                if ($this->type === self::CONTENT_JSON && is_array($data)) $data = json_encode($data);
                if ($this->type === self::CONTENT_FORM && is_array($data)) $data = http_build_query($data);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }
        }

        // Sets the URL
        curl_setopt($curl, CURLOPT_URL, $url);

        // Ensure to retrieve the response headers
        $headers = [];
        curl_setopt($curl, CURLOPT_HEADERFUNCTION, function ($ch, $header) use (&$headers) {
            $len = strlen($header);
            $header = explode(':', $header, 2);
            if (count($header) < 2) return $len;
            $key = trim($header[0]);
            if (isset($headers[$key])) {
                $headers[$key] = implode(', ', [$headers[$key], trim($header[1])]);
            } else {
                $headers[$key] = trim($header[1]);
            }
            return $len;
        });

        // Fetches the request
        $response = curl_exec($curl);

        // Gets the response info
        $info = curl_getinfo($curl);

        // Checks for the result
        if ($response === false) {
            $result = false;
        } else {
            $result = new Element([
                'status' => $info['http_code'],
                'success' => (bool)($info['http_code'] >= 200 && $info['http_code'] < 300),
                'failed' => (bool)($info['http_code'] >= 400),
                'type' => $info['content_type'] ?? null,
                'body' => $response,
                'json' => new Element(json_decode($response, true) ?? []),
                'redirects' => $info['redirect_count'],
                'headers' => new Element($headers)
            ]);
        }

        // Error handling
        if ($this->throw && curl_errno($curl)) throw new RequestException($url, curl_error($curl), curl_errno($curl), $result);

        // Closes the connection
        curl_close($curl);

        // Returns the result
        return $result;
    }
}
