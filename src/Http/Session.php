<?php

namespace Glowie\Core\Http;

use Glowie\Core\Exception\FileException;
use Glowie\Core\Collection;
use Config;
use Glowie\Core\Tools\Validator;
use Util;
use JsonSerializable;

/**
 * Session manager for Glowie application.
 * @category Session manager
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://gabrielsilva.dev.br/glowie
 * @see https://gabrielsilva.dev.br/glowie/docs/latest/forms-and-data/session
 */
class Session implements JsonSerializable
{

    /**
     * Session flash data.
     * @var array
     */
    private static $flash = [];

    /**
     * Application name.
     * @var string
     */
    private static $appName;

    /**
     * Starts a new session or resumes the existing one.
     * @param array $data (Optional) An associative array with the initial data to store in the session.\
     * **This replaces the existing session data!**
     */
    public function __construct(array $data = [])
    {
        if (!self::$appName) self::$appName = Util::snakeCase(Config::get('app_name', 'Glowie'));
        if (!isset($_SESSION)) session_start();
        if (!empty($data)) $_SESSION = $data;
        self::$flash = $this->get(self::$appName . '.flash') ?? [];
    }

    /**
     * Registers the session save path and INI settings.
     */
    public static function register()
    {
        // Save path
        $sessdir = Config::get('session.path', Util::location('storage/session'));
        if (!is_writable($sessdir)) throw new FileException('Session path "' . $sessdir . '" is not writable, please check your chmod settings');
        session_save_path($sessdir);

        // INI settings
        ini_set('session.name', Config::get('session.name', 'app_session'));
        ini_set('session.gc_divisor', (string)Config::get('session.gc_cleaning', 50));
        ini_set('session.gc_maxlifetime', (string)Config::get('session.lifetime', 120));
        ini_set('session.cookie_httponly', (string)Config::get('session.restrict', true));
        ini_set('session.cookie_secure', (string)Config::get('session.secure', false));
        ini_set('session.gc_probability', '1');
        ini_set('session.use_cookies', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');
    }

    /**
     * Gets the value associated to a key in the session data.
     * @param string $key Key to get value.
     * @return mixed Returns the value if exists or null if there is none.
     */
    public function __get(string $key)
    {
        return $this->get($key);
    }

    /**
     * Gets the value associated to a key in the session data.
     * @param string $key Key to get value (accepts dot notation keys).
     * @param mixed $default (Optional) Default value to return if the key does not exist.
     * @return mixed Returns the value if exists or the default if not.
     */
    public function get(string $key, $default = null)
    {
        return Util::arrayGet($_SESSION, $key, $default);
    }

    /**
     * Sets the value for a key in the session data.
     * @param string $key Key to set value.
     * @param mixed $value Value to set.
     */
    public function __set(string $key, $value)
    {
        $this->set($key, $value, true);
    }

    /**
     * Sets the value for a key in the session data.
     * @param string|array $key Key to set value (accepts dot notation keys). You can also pass an associative array\
     * of values to set at once and they will be merged into the session data.
     * @param mixed $value Value to set.
     * @param bool $ignoreDot (Optional) Ignore dot notation keys.
     * @return Session Current Session instance for nested calls.
     */
    public function set($key, $value = null, bool $ignoreDot = false)
    {
        if (is_array($key)) {
            $_SESSION = array_merge($_SESSION, $key);
        } else {
            if ($ignoreDot) {
                $_SESSION[$key] = $value;
            } else {
                Util::arraySet($_SESSION, $key, $value);
            }
        }
        return $this;
    }

    /**
     * Sets an encrypted string value in the session data.
     * @param string $key Key to set value (accepts dot notation keys).
     * @param string $value Value to be store encrypted.
     * @param bool $ignoreDot (Optional) Ignore dot notation keys.
     * @return Session Current Session instance for nested calls.
     */
    public function setEncrypted(string $key, string $value, bool $ignoreDot = false)
    {
        return $this->set($key, Util::encryptString($value), $ignoreDot);
    }

    /**
     * Gets an encrypted string value from the session data.
     * @param string $key Key to get value (accepts dot notation keys).
     * @param mixed $default (Optional) Default value to return if the key does not exist.
     * @return mixed Returns the decrypted value if exists or the default if not.
     */
    public function getEncrypted(string $key, $default = null)
    {
        $value = $this->get($key);
        if (!$value) return $default;
        return Util::decryptString($value);
    }

    /**
     * Removes the associated key value from the session data.
     * @param string $key Key to delete value.
     */
    public function __unset(string $key)
    {
        $this->remove($key, true);
    }

    /**
     * Removes the associated key value from the session data.
     * @param string|array $key Key to delete value (accepts dot notation keys). You can also use an array of keys to remove.
     * @param bool $ignoreDot (Optional) Ignore dot notation keys.
     * @return Session Current Session instance for nested calls.
     */
    public function remove($key, bool $ignoreDot = false)
    {
        if (is_array($key)) {
            foreach ($key as $item) $this->remove($item, $ignoreDot);
        } else {
            if (!$ignoreDot) {
                Util::arrayDelete($_SESSION, $key);
            } else {
                unset($_SESSION[$key]);
            }
        }
        return $this;
    }

    /**
     * Removes all session data, except the one that matches the specified key.
     * @param string|array $key Key to keep. You can also use an array of keys to keep.
     * @return Session Current Session instance for nested calls.
     */
    public function only($key)
    {
        $_SESSION = array_intersect_key($_SESSION, array_flip((array)$key));
        return $this;
    }

    /**
     * Checks if any value has been associated to a key in the session data.
     * @param string $key Key to check.
     * @return bool Returns true or false.
     */
    public function __isset(string $key)
    {
        return $this->has($key);
    }

    /**
     * Checks if any value has been associated to a key in the session data.
     * @param string|array $key Key to check (accepts dot notation keys). You can also use an array of keys.
     * @return bool Returns true or false.
     */
    public function has($key)
    {
        $result = false;
        foreach ((array)$key as $item) {
            if ($result) break;
            $result = Util::arrayGet($_SESSION, $item) !== null;
        }
        return $result;
    }

    /**
     * Deletes all data from the session.
     * @return Session Current Session instance for nested calls.
     */
    public function flush()
    {
        $_SESSION = [];
        self::$flash = [];
        return $this;
    }

    /**
     * Gets the session data as an associative array.
     * @return array The resulting array.
     */
    public function toArray()
    {
        return $_SESSION;
    }

    /**
     * Gets the session data as a Collection.
     * @return Collection The session data as a Collection.
     */
    public function toCollection()
    {
        return new Collection($_SESSION);
    }

    /**
     * Returns the serializable JSON data for the session.
     * @return array Session data as an associative array.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Gets the session data as JSON.
     * @param int $flags (Optional) JSON encoding flags (same as in `json_encode()` function).
     * @param int $depth (Optional) JSON encoding maximum depth (same as in `json_encode()` function).
     * @return string The resulting JSON string.
     */
    public function toJson(int $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES, int $depth = 512)
    {
        return empty($_SESSION) ? '{}' : json_encode($_SESSION, $flags, $depth);
    }

    /**
     * Dumps the session data.
     * @param bool $plain (Optional) Dump data as plain text instead of HTML.
     */
    public function dump(bool $plain = false)
    {
        Util::dump($this, $plain);
    }

    /**
     * Gets the session data as a string (data will be serialized as JSON).
     * @return string The resulting JSON string.
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * Session debugging information.
     */
    public function __debugInfo()
    {
        return $_SESSION;
    }

    /**
     * Sets the value for a key in the session flash data.
     * @param string $key Key to set value.
     * @param mixed $value Value to set.
     * @return Session Current Session instance for nested calls.
     */
    public function setFlash(string $key, $value)
    {
        self::$flash = $this->get(self::$appName . '.flash') ?? [];
        self::$flash[$key] = $value;
        return $this->set(self::$appName . '.flash', self::$flash);
    }

    /**
     * Gets the value associated to a key in the session flash data, then deletes it.
     * @param string $key Key to get value.
     * @param mixed $default (Optional) Default value to return if the key does not exist.
     * @return mixed Returns the value if exists or the default if not.
     */
    public function getFlash(string $key, $default = null)
    {
        self::$flash = $this->get(self::$appName . '.flash') ?? [];
        if (array_key_exists($key, self::$flash)) {
            $value = self::$flash[$key];
            unset(self::$flash[$key]);
            $this->set(self::$appName . '.flash', self::$flash);
            return $value;
        } else {
            return $default;
        }
    }

    /**
     * Persists the current session data to a long-life cookie.
     * @param int $expires (Optional) Cookie expiration time in seconds.
     */
    public function persist(int $expires = Cookies::EXPIRES_DAY)
    {
        $cookies = new Cookies();
        $cookies->set(session_name(), session_id(), $expires);
    }

    /**
     * Flushes the persistent cookie with the session data.\
     * **This also deletes the whole session data.**
     */
    public function flushPersistent()
    {
        $cookies = new Cookies();
        $cookies->remove(session_name());
        $this->flush();
    }

    /**
     * Validates the session data using unique validation rules for each one of the fields.
     * @param array $rules Associative array with validation rules for each field.
     * @param bool $bail (Optional) Stop validation of each field after first failure found.
     * @param bool $bailAll (Optional) Stop validation of all fields after first failure found.
     * @return bool Returns true if all rules passed for all fields, false otherwise.
     */
    public function validate(array $rules, bool $bail = false, bool $bailAll = false)
    {
        return (new Validator())->validateFields($this->toArray(), $rules, $bail, $bailAll);
    }

    /**
     * Validates the session data using the same rules for all values.
     * @param string|array $rules Validation rules for the data. Can be a single rule or an array of rules.
     * @param bool $bail (Optional) Stop validation of each value after first failure found.
     * @param bool $bailAll (Optional) Stop validation of all values after first failure found.
     * @return bool Returns true if all rules passed for all values, false otherwise.
     */
    public function validateAll($rules, bool $bail = false, bool $bailAll = false)
    {
        return (new Validator())->validateMultiple($this->toArray(), $rules, $bail, $bailAll);
    }
}
