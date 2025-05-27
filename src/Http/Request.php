<?php

namespace Glowie\Core\Http;

use Util;
use Glowie\Core\Collection;
use Glowie\Core\Element;
use Glowie\Core\Tools\Uploader;
use Glowie\Core\Tools\Validator;
use Glowie\Core\Traits\ElementTrait;
use JsonSerializable;

/**
 * Request handler for Glowie application.
 * @category Request
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 * @see https://glowie.gabrielsilva.dev.br/docs/latest/basic-application-modules/request
 */
class Request implements JsonSerializable
{
    use ElementTrait;

    /**
     * Request headers.
     * @var array
     */
    private static $headers;

    /**
     * Request JSON data.
     * @var Element
     */
    private static $json;

    /**
     * Validator instance.
     * @var Validator
     */
    private $__validator;

    /**
     * Creates a new Request handler instance.
     */
    public function __construct()
    {
        // Parse headers and JSON data, if any
        self::$headers = array_change_key_case(getallheaders(), CASE_LOWER);
        self::$json = new Element(json_decode($this->getBody(), true) ?? []);

        // Parse request variables
        $vars = array_merge(Rails::getParams()->toArray(), $_GET, $_POST, $this->fromBody()->toArray());
        $this->__constructTrait($vars);
    }

    /**
     * Returns the request uploaded files.
     * @param string $input Valid file input field name.
     * @return array|null Returns an array of the files, each one as an Element, or null if no files were uploaded.
     */
    public function getFiles(string $input)
    {
        $uploader = new Uploader();
        if (empty($_FILES[$input]['name'])) return null;
        return $uploader->arrangeFiles($_FILES[$input]);
    }

    /**
     * Returns a single file from the request uploaded files.
     * @param string $input Valid file input field name.
     * @return Element|null Returns the file as an Element, or null if no files were uploaded.
     */
    public function getFile(string $input)
    {
        return $this->getFiles($input)[0] ?? null;
    }

    /**
     * Returns the request full URL.
     * @return string Request full URL.
     */
    public function getURL()
    {
        return (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

    /**
     * Returns the request clean URI (without hostname or query strings).
     * @return string Request clean URI.
     */
    public function getURI()
    {
        $result = trim(substr(trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'), strlen(APP_FOLDER)), '/');
        return !Util::isEmpty($result) ? $result : '/';
    }

    /**
     * Returns the request hostname address.
     * @param bool $withHttp (Optional) Include http/https in address.
     * @return string Request hostname.
     */
    public function getHostname(bool $withHttp = false)
    {
        return ($withHttp ? (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') : '') . $_SERVER['HTTP_HOST'];
    }

    /**
     * Gets the request full query string, without `?`.
     * @return string Request query string.
     */
    public function getQueryString()
    {
        return parse_url($this->getURL(), PHP_URL_QUERY) ?? '';
    }

    /**
     * Gets the request anchor fragment, without `#`.
     * @return string Request fragment.
     */
    public function getFragment()
    {
        return parse_url($this->getURL(), PHP_URL_FRAGMENT) ?? '';
    }

    /**
     * Gets the request port.
     * @return int Request port.
     */
    public function getPort()
    {
        return (int)($_SERVER['SERVER_PORT'] ?? 80);
    }

    /**
     * Returns the request IP address.
     * @return string Request IP address if valid or `0.0.0.0` if not.
     */
    public function getIPAddress()
    {
        return $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_FORWARDED'] ?? $_SERVER['HTTP_FORWARDED_FOR'] ?? $_SERVER['HTTP_FORWARDED_FOR'] ?? $_SERVER['HTTP_FORWARDED'] ?? $_SERVER['HTTP_CLUSTER_CLIENT_IP'] ?? $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Returns the raw request body as a string.
     * @return string Raw request body.
     */
    public function getBody()
    {
        return file_get_contents('php://input');
    }

    /**
     * Gets the request vars from `GET` method only.
     * @return Element Returns an Element with the request vars.
     */
    public function fromGet()
    {
        return new Element($_GET);
    }

    /**
     * Gets the request vars from `POST` method only.
     * @return Element Returns an Element with the request vars.
     */
    public function fromPost()
    {
        return new Element($_POST);
    }

    /**
     * Gets the server variables.
     * @return Element Returns an Element with the server vars.
     */
    public function fromServer()
    {
        return new Element($_SERVER ?? []);
    }

    /**
     * Gets the route parameters.
     * @return Element Returns an Element with the route params.
     */
    public function fromRoute()
    {
        return Rails::getParams();
    }

    /**
     * Gets the request vars from the request body only.
     * @return Element Returns an Element with the request vars.
     */
    public function fromBody()
    {
        $params = [];
        parse_str($this->getBody(), $params);
        return new Element($params);
    }

    /**
     * Returns a JSON key from the request.
     * @param string|null $key (Optional) Key to get value (accepts dot notation keys). Leave empty to get the whole JSON Element.
     * @param mixed $default (Optional) Default value to return if the key does not exist.
     * @return mixed Returns the value if the key exists (or the default if not) or the JSON Element if a key is not provided.
     */
    public function getJson(?string $key = null, $default = null)
    {
        if (Util::isEmpty($key)) return self::$json;
        return self::$json->get($key, $default);
    }

    /**
     * Gets a value from the request body casted as a boolean.
     * @param string $key Key to get value.
     * @param bool $default (Optional) Default value to return if the key does not exist.
     * @return bool Returns the value (casted as a boolean) if exists or the default if not.
     */
    public function getBool(string $key, bool $default = false)
    {
        return filter_var($this->get($key, $default), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Returns the request method.
     * @return string Request method.
     */
    public function getMethod()
    {
        if (!empty($_POST['_method']) && in_array(strtoupper($_POST['_method']), ['HEAD', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'])) return strtoupper($_POST['_method']);
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * Returns **true** if the request was made using GET method.
     * @return bool Returns true or false matching the request method.
     */
    public function isGet()
    {
        return $this->getMethod() === 'GET';
    }

    /**
     * Returns **true** if the request was made using POST method.
     * @return bool Returns true or false matching the request method.
     */
    public function isPost()
    {
        return $this->getMethod() === 'POST';
    }

    /**
     * Returns **true** if the request was made using PUT method.
     * @return bool Returns true or false matching the request method.
     */
    public function isPut()
    {
        return $this->getMethod() === 'PUT';
    }

    /**
     * Returns **true** if the request was made using DELETE method.
     * @return bool Returns true or false matching the request method.
     */
    public function isDelete()
    {
        return $this->getMethod() === 'DELETE';
    }

    /**
     * Returns **true** if the request was made using PATCH method.
     * @return bool Returns true or false matching the request method.
     */
    public function isPatch()
    {
        return $this->getMethod() === 'PATCH';
    }

    /**
     * Returns **true** if the request was made using HEAD method.
     * @return bool Returns true or false matching the request method.
     */
    public function isHead()
    {
        return $this->getMethod() === 'HEAD';
    }

    /**
     * Returns **true** if the request was made using OPTIONS method.
     * @return bool Returns true or false matching the request method.
     */
    public function isOptions()
    {
        return $this->getMethod() === 'OPTIONS';
    }

    /**
     * Returns the previous URL where the user was.\
     * **Note:** This information relies in the `Referer` header.
     * @return string|null Returns the URL if the header exists or null if not.
     */
    public function getPreviousUrl()
    {
        return $this->getHeader('Referer');
    }

    /**
     * Checks if the request URI matches a pattern.
     * @param string|array Pattern to match or an array of patterns to search inside.
     * @return bool Return true if the request URI matches one of the patterns, false otherwise.
     */
    public function match($pattern)
    {
        $path = $this->getURI();
        foreach ((array)$pattern as $pattern) {
            $regex = '#^' . str_replace('\*', '.*', preg_quote(trim($pattern, '/'), '#')) . '$#';
            if (preg_match($regex, $path)) return true;
        }
        return false;
    }

    /**
     * Gets the value of a header.
     * @param string $name Header name to get.
     * @param mixed $default (Optional) Default value to return if the header does not exist.
     * @return mixed Returns the value if exists or the default if not.
     */
    public function getHeader(string $name, $default = null)
    {
        return self::$headers[strtolower($name)] ?? $default;
    }

    /**
     * Gets all the request headers as a Collection.
     * @return Collection Returns a Collection with the headers.
     */
    public function getHeaders()
    {
        return new Collection(getallheaders());
    }

    /**
     * Checks if a header is present.
     * @param string $name Header name to check.
     * @return bool Returns true if the header is present, false otherwise.
     */
    public function hasHeader(string $name)
    {
        return !is_null($this->getHeader($name));
    }

    /**
     * Gets the `Content-Type` header.
     * @return string|null Returns the header value if exists or null if there is none.
     */
    public function getContentType()
    {
        return $this->getHeader('Content-Type');
    }

    /**
     * Gets the `User-Agent` header.
     * @return string|null Returns the header value if exists or null if there is none.
     */
    public function getUserAgent()
    {
        return $this->getHeader('User-Agent');
    }

    /**
     * Gets the `Accept` header.
     * @return string|null Returns the header value if exists or null if there is none.
     */
    public function getAccept()
    {
        return $this->getHeader('Accept');
    }

    /**
     * Returns if the request was made using AJAX.\
     * **Note:** This information relies in the `X-Requested-With` header.
     * @return bool True if is AJAX or false if not or header is not present.
     */
    public function isAjax()
    {
        return $this->getHeader('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * Return if the request body was made using JSON.\
     * **Note:** This information relies in the `Content-Type` header, or the request body itself.
     * @return bool True if JSON request.
     */
    public function isJson()
    {
        return ($this->getContentType() === 'application/json' || Util::isJson($this->getBody()));
    }

    /**
     * Returns if the request was made using a mobile device.\
     * **Note:** This information relies in the `User-Agent` header.
     * @return bool True if a mobile device identifier is present in the header, false otherwise or header is not present.
     */
    public function isMobile()
    {
        return preg_match("/(android|webos|avantgo|iphone|ipad|ipod|blackberry|iemobile|bolt|boost|cricket|docomo|fone|hiptop|mini|opera mini|kitkat|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $this->getHeader('User-Agent', ''));
    }

    /**
     * Returns if the request was made using a secure connection (HTTPS).
     * @return bool True if secure or false if not.
     */
    public function isSecure()
    {
        return isset($_SERVER['HTTPS']);
    }

    /**
     * Compares an input value against the session CSRF token if exists.
     * @param string $input Input value to compare.
     * @return bool Returns true if the tokens match or false if not.
     */
    public function checkCsrfToken(string $input)
    {
        $session = new Session();
        if (!$session->has('CSRF_TOKEN')) return false;
        return hash_equals($session->get('CSRF_TOKEN'), $input);
    }

    /**
     * Validates the request data using unique validation rules for each one of the fields.
     * @param array $rules Associative array with validation rules for each field.
     * @param bool $bail (Optional) Stop validation of each field after first failure found.
     * @param bool $bailAll (Optional) Stop validation of all fields after first failure found.
     * @return bool Returns true if all rules passed for all fields, false otherwise.
     */
    public function validate(array $rules, bool $bail = false, bool $bailAll = false)
    {
        return $this->getValidator()->validateFields($this->toArray(), $rules, $bail, $bailAll);
    }

    /**
     * Validates the request data using the same rules for all values.
     * @param string|array $rules Validation rules for the data. Can be a single rule or an array of rules.
     * @param bool $bail (Optional) Stop validation of each value after first failure found.
     * @param bool $bailAll (Optional) Stop validation of all values after first failure found.
     * @return bool Returns true if all rules passed for all values, false otherwise.
     */
    public function validateAll($rules, bool $bail = false, bool $bailAll = false)
    {
        return $this->getValidator()->validateMultiple($this->toArray(), $rules, $bail, $bailAll);
    }

    /**
     * Gets the Validator instance associated with the data.
     * @return Validator The validator instance.
     */
    public function getValidator()
    {
        if (!$this->__validator) $this->__validator = new Validator();
        return $this->__validator;
    }
}
