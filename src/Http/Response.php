<?php

namespace Glowie\Core\Http;

use Util;
use Config;
use SimpleXMLElement;
use Glowie\Core\View\Buffer;
use Glowie\Core\Collection;
use Glowie\Core\Exception\FileException;

/**
 * Response handler for Glowie application.
 * @category Response
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 * @see https://glowie.gabrielsilva.dev.br/docs/latest/basic-application-modules/response
 */
class Response
{

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
     * HTTP 204 No Content status code.
     * @var int
     */
    public const HTTP_NO_CONTENT = 204;

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
     * @return Response Current Response instance for nested calls.
     */
    public function applyCors()
    {
        // Check if CORS is enabled
        if (!Config::get('cors.enabled', true)) return $this;

        // Apply CORS headers
        $this->setHeader('Access-Control-Allow-Methods', Config::get('cors.allowed_methods', ['*']));
        $this->setHeader('Access-Control-Allow-Origin', Config::get('cors.allowed_origins', ['*']));
        $this->setHeader('Access-Control-Allow-Headers', Config::get('cors.allowed_headers', ['*']));
        $this->setHeader('Access-Control-Max-Age', Config::get('cors.max_age', 0));

        $exposedHeaders = Config::get('cors.exposed_headers', []);
        if (!empty($exposedHeaders)) {
            $this->setHeader('Access-Control-Expose-Headers', $exposedHeaders);
        }

        if (Config::get('cors.allow_credentials', false)) {
            $this->setHeader('Access-Control-Allow-Credentials', 'true');
        }

        // Handle preflight requests
        $this->handlePreflight();
        return $this;
    }

    /**
     * Sets the HTTP status code for the response.
     * @param int $code HTTP status code to set.
     * @param string $message (Optional) Custom reason phrase to set.
     * @return Response Current Response instance for nested calls.
     */
    public function setStatusCode(int $code, string $message = '')
    {
        if (empty($message)) {
            http_response_code($code);
        } else {
            header("HTTP/1.0 {$code} {$message}", true, $code);
        }
        return $this;
    }

    /**
     * Returns the current HTTP status code set for the response.
     * @return int Current status code.
     */
    public function getStatusCode()
    {
        return http_response_code();
    }

    /**
     * Sets a **403 Forbidden** HTTP response code.
     * @return Response Current Response instance for nested calls.
     */
    public function deny()
    {
        return $this->setStatusCode(self::HTTP_FORBIDDEN);
    }

    /**
     * Sets a **404 Not Found** HTTP response code.
     * @return Response Current Response instance for nested calls.
     */
    public function notFound()
    {
        return $this->setStatusCode(self::HTTP_NOT_FOUND);
    }

    /**
     * Sets a **500 Internal Server Error** HTTP response code.
     * @return Response Current Response instance for nested calls.
     */
    public function fail()
    {
        return $this->setStatusCode(self::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Sets a **429 Too Many Requests** HTTP response code.
     * @return Response Current Response instance for nested calls.
     */
    public function rateLimit()
    {
        return $this->setStatusCode(self::HTTP_TOO_MANY_REQUESTS);
    }

    /**
     * Sets a new header value or replaces the existing one.
     * @param string $name Header name to set.
     * @param string|array $value Header value to set. Can also be an array of values.
     * @param int $code (Optional) HTTP response code to force.
     * @return Response Current Response instance for nested calls.
     */
    public function setHeader(string $name, $value, int $code = 0)
    {
        $value = implode(', ', (array)$value);
        header("{$name}: {$value}", true, $code);
        return $this;
    }

    /**
     * Sets a new header value or appends the value to the existing one.
     * @param string $name Header name to set.
     * @param string|array $value Header value to set.
     * @param int $code (Optional) HTTP response code to force.
     * @return Response Current Response instance for nested calls.
     */
    public function appendHeader(string $name, $value, int $code = 0)
    {
        $value = implode(', ', (array)$value);
        header("{$name}: {$value}", false, $code);
        return $this;
    }

    /**
     * Gets a list of the response headers.
     * @return Collection Returns a Collection with the headers.
     */
    public function getHeaders()
    {
        $list = [];
        foreach (headers_list() as $header) {
            $header = explode(':', $header, 2);
            $list[trim($header[0])] = trim($header[1] ?? '');
        }
        return new Collection($list);
    }

    /**
     * Sets a basic `Authorization` header with username and password.
     * @param string $username Username to set.
     * @param string $password Password to set.
     * @return Response Current Response instance for nested calls.
     */
    public function setAuthorization(string $username, string $password)
    {
        return $this->setHeader('Authorization', 'Basic ' . base64_encode("{$username}:{$password}"));
    }

    /**
     * Sets a bearer `Authorization` header with a token.
     * @param string $token Token to set.
     * @return Response Current Response instance for nested calls.
     */
    public function setBearer(string $token)
    {
        return $this->setHeader('Authorization', 'Bearer ' . $token);
    }

    /**
     * Sets the `Content-Type` header.
     * @param string $type Content-Type to set.
     * @return Response Current Response instance for nested calls.
     */
    public function setContentType(string $type)
    {
        return $this->setHeader('Content-Type', $type);
    }

    /**
     * Forces the download of the content by setting the `Content-Disposition` header.
     * @param string $filename Filename to download including extension.
     * @return Response Current Response instance for nested calls.
     */
    public function setDownload(string $filename)
    {
        return $this->setHeader('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Disables the browser caching by setting the `Cache-Control` header.
     * @return Response Current Response instance for nested calls.
     */
    public function disableCache()
    {
        return $this->setHeader('Cache-Control', 'no-store, max-age=0, no-cache');
    }

    /**
     * Sends a raw plain text body to the response.
     * @param string $content Content to set as the body.
     * @param string $type (Optional) Content type header to set, defaults to `text/plain`.
     * @return Response Current Response instance for nested calls.
     */
    public function setBody(string $content, string $type = self::CONTENT_PLAIN)
    {
        if (Buffer::isActive()) Buffer::clean();
        $this->setContentType($type);
        echo $content;
        return $this;
    }

    /**
     * Sends a JSON output to the response.
     * @param array|Element $data Associative array with data to encode to JSON. You can also use an Element.
     * @param int $flags (Optional) JSON encoding flags (same as in `json_encode()` function).
     * @param int $depth (Optional) JSON encoding maximum depth (same as in `json_encode()` function).
     * @return Response Current Response instance for nested calls.
     */
    public function setJson($data, int $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES, int $depth = 512)
    {
        if (Buffer::isActive()) Buffer::clean();
        $this->setContentType(self::CONTENT_JSON);
        if (is_callable([$data, 'toArray'])) $data = $data->toArray();
        echo json_encode($data, $flags, $depth);
        return $this;
    }

    /**
     * Sends a XML output to the response.
     * @param array|Element $data Associative array with data to encode to XML. You can also use an Element.
     * @param string $root (Optional) Name of the XML root element.
     * @return Response Current Response instance for nested calls.
     */
    public function setXML($data, string $root = 'root')
    {
        if (Buffer::isActive()) Buffer::clean();
        $this->setContentType(self::CONTENT_XML);
        $xml = new SimpleXMLElement("<?xml version=\"1.0\"?><{$root}></{$root}>");
        if (is_callable([$data, 'toArray'])) $data = $data->toArray();
        $this->arrayToXML($data, $xml);
        echo $xml->asXML();
        return $this;
    }

    /**
     * Streams a file content as the response.
     * @param string $filename Filename to stream the content from.
     * @return Response Current Response instance for nested calls.
     */
    public function setFile(string $filename)
    {
        if (!is_file($filename)) throw new FileException('"' . $filename . '" does not exist!');
        if (Buffer::isActive()) Buffer::clean();
        $this->setContentType(mime_content_type($filename) || self::CONTENT_PLAIN);
        $this->setHeader('Content-Length', filesize($filename));
        readfile($filename);
        return $this;
    }

    /**
     * Sets the value for a key in the session flash data before redirecting.
     * @param string $key Key to set value.
     * @param mixed $value Value to set.
     * @return Response Current Response instance for nested calls.
     */
    public function withFlash(string $key, $value)
    {
        Session::make()->setFlash($key, $value);
        return $this;
    }

    /**
     * Flashes the current request data in the session before redirecting.
     * @param array $name (Optional) Array of input names to include. Leave empty for all.
     * @return Response Current Response instance for nested calls.
     */
    public function withInputs(array $name = [])
    {
        $data = Rails::getRequest();
        if (!empty($name)) $data->only($name);
        return $this->withFlash('input', $data->toArray());
    }

    /**
     * Redirects to a relative or full URL.
     * @param string $destination Target URL to redirect to.
     * @param int $code (Optional) HTTP status code to pass with the redirect.
     * @return Response Current Response instance for nested calls.
     */
    public function redirect(string $destination, int $code = self::HTTP_TEMPORARY_REDIRECT)
    {
        if (Buffer::isActive()) Buffer::clean();
        $this->setStatusCode($code);
        $this->setHeader('Location', $destination, $code);
        return $this;
    }

    /**
     * Redirects to an URL relative to the application path.
     * @param string $path (Optional) Path to append to the base URL.
     * @param int $code (Optional) HTTP status code to pass with the redirect.
     * @return Response Current Response instance for nested calls.
     */
    public function redirectBase(string $path = '', int $code = self::HTTP_TEMPORARY_REDIRECT)
    {
        return $this->redirect(Util::baseUrl($path), $code);
    }

    /**
     * Redirects to a named route.
     * @param string $route Route name.
     * @param array $params (Optional) Route parameters to bind into the URL.
     * @param int $code (Optional) HTTP status code to pass with the redirect.
     * @return Response Current Response instance for nested calls.
     */
    public function redirectRoute(string $route, array $params = [], int $code = self::HTTP_TEMPORARY_REDIRECT)
    {
        return $this->redirect(Util::route($route, $params), $code);
    }

    /**
     * Converts an array to XML.
     * @param array $data Array with data to convert to XML.
     * @param SimpleXMLElement $xml_data XML Element to append data.
     */
    private function arrayToXML(array $data, &$xml_data)
    {
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

    /**
     * Handles a preflight request with CORS.
     */
    private function handlePreflight()
    {
        // Checks if is a preflight request
        $request = Rails::getRequest();
        if (!$request->isOptions() || !$request->hasHeader('Access-Control-Request-Method')) return;

        // Sets the 204 status code
        $this->setStatusCode(self::HTTP_NO_CONTENT);

        // Sets the origin header if all allowed
        $response = $this->getHeaders();
        $supportCredentials = $response->has('Access-Control-Allow-Credentials');
        $allowedOrigins = $response->get('Access-Control-Allow-Origin');
        $origin = $request->getHeader('Origin');

        if ($supportCredentials && $allowedOrigins === '*' && $origin) {
            $this->setHeader('Access-Control-Allow-Origin', $origin);
        }

        // Ends the request immediately
        exit;
    }
}
