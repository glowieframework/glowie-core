<?php

namespace Glowie\Core\Tools;

use Glowie\Core\Http\Session;
use Glowie\Core\Http\Cookies;
use Exception;
use Closure;
use Util;
use Config;

/**
 * Session auth handler for Glowie application.
 * @category Authenticator
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://gabrielsilva.dev.br/glowie
 * @see https://gabrielsilva.dev.br/glowie/docs/latest/extra/authentication
 */
class Authenticator
{

    /**
     * Authentication success code.
     * @var int
     */
    public const ERR_AUTH_SUCCESS = 0;

    /**
     * Empty login credentials error code.
     * @var int
     */
    public const ERR_EMPTY_DATA = 1;

    /**
     * User not found error code.
     * @var int
     */
    public const ERR_NO_USER = 2;

    /**
     * Wrong password error code.
     * @var int
     */
    public const ERR_WRONG_PASSWORD = 3;

    /**
     * Last authentication error.
     * @var int|null
     */
    private $error = null;

    /**
     * Current auth guard.
     * @var string
     */
    private $guard = 'default';

    /**
     * Current authenticated users for each guard.
     * @var array
     */
    private static $user = [];

    /**
     * Creates an Authenticator instance.
     * @param string $guard (Optional) Authentication guard name (from your app configuration).
     */
    public function __construct(string $guard = 'default')
    {
        $this->setGuard($guard);
    }

    /**
     * Sets the authentication guard (from your app configuration).
     * @param string $guard Guard name.
     * @return Authenticator Current Authenticator instance for nested calls.
     */
    public function setGuard(string $guard)
    {
        if (!Config::has('auth.' . $guard)) throw new Exception('Authenticator: Unknown guard "' . $guard . '"');
        $this->guard = $guard;
        return $this;
    }

    /**
     * Authenticates an user from the database and store its data in the session.
     * @param string $user Username to authenticate.
     * @param string $password Password to authenticate.
     * @param array|Closure $conditions (Optional) Associative array of aditional fields to use while querying for the user.\
     * You can also pass a Closure to directly modify the query builder.
     * @return bool Returns true if authentication is successful, false otherwise.
     */
    public function login(string $user, string $password, $conditions = [])
    {
        // Check for empty login credentials
        if (Util::isEmpty($user) || Util::isEmpty($password)) {
            $this->error = self::ERR_EMPTY_DATA;
            self::$user[$this->guard] = null;
            return false;
        }

        // Create model instance
        $model = Config::get('auth.' . $this->guard . '.model');
        if (!$model || !class_exists($model)) throw new Exception("Authenticator: \"{$model}\" was not found");
        $model = new $model;

        // Get auth fields
        $userField = Config::get('auth.' . $this->guard . '.user_field', 'email');
        $passwordField = Config::get('auth.' . $this->guard . '.password_field', 'password');

        // Fetch user information
        if ($conditions instanceof Closure) {
            call_user_func_array($conditions, [&$model, &$user, &$password]);
            $user = $model->findAndFillBy([$userField => $user]);
        } else {
            $user = $model->findAndFillBy(array_merge([$userField => $user], $conditions));
        }

        if (!$user) {
            $this->error = self::ERR_NO_USER;
            self::$user[$this->guard] = null;
            return false;
        }

        // Check password
        if (password_verify($password, $user->get($passwordField))) {
            self::$user[$this->guard] = $user;
            $this->error = self::ERR_AUTH_SUCCESS;

            // Save credentials in session
            (new Session())->setEncrypted('glowie.auth.' . $this->guard, $user->getPrimary());
            return true;
        } else {
            $this->error = self::ERR_WRONG_PASSWORD;
            self::$user[$this->guard] = null;
            return false;
        }
    }

    /**
     * Checks if an user is authenticated.
     * @return bool True or false.
     */
    public function check()
    {
        return !is_null($this->getUser());
    }

    /**
     * Gets the current authenticated user.
     * @return Model|null Returns the user Model instance if authenticated, null otherwise.
     */
    public function getUser()
    {
        // Check for fetched user
        if (isset(self::$user[$this->guard])) return self::$user[$this->guard];

        // Get from session
        $user = (new Session())->getEncrypted('glowie.auth.' . $this->guard);

        if (!is_null($user)) {
            // Create model instance
            $model = Config::get('auth.' . $this->guard . '.model');
            if (!$model || !class_exists($model)) throw new Exception("Authenticator: \"{$model}\" was not found");
            $model = new $model;

            // Fetch user information
            $user = $model->findAndFill($user);
            if ($user !== false) self::$user[$this->guard] = $user;
        }

        // Return fetched user, if found
        return self::$user[$this->guard] ?? null;
    }

    /**
     * Gets the id (or other primary key value) from the authenticated user.
     * @return mixed Returns the primary key value if authenticated, null otherwise.
     */
    public function getUserId()
    {
        if (!isset(self::$user[$this->guard])) {
            $user = $this->getUser();
            if (!$user) return null;
        }

        return self::$user[$this->guard]->getPrimary() ?? null;
    }

    /**
     * Refreshes the authenticated user model from the database using its primary key.
     * @return bool Returns true on success, false otherwise.
     */
    public function refresh()
    {
        if (!isset(self::$user[$this->guard])) {
            $user = $this->getUser();
            if (!$user) return false;
        }

        return self::$user[$this->guard]->refresh() ?? false;
    }

    /**
     * Persists the login for a longer time than the default session expiration.
     * @param int $expires (Optional) Expiration time in seconds.
     * @return bool Returns true on success, false if user is not authenticated yet.
     */
    public function remember(int $expires = Cookies::EXPIRES_DAY)
    {
        $session = new Session();
        if (!$session->has('glowie.auth.' . $this->guard)) return false;
        $session->persist($expires);
        return true;
    }

    /**
     * Logout the authenticated user.
     * @return bool Returns true on success, false if user is not authenticated yet.
     */
    public function logout()
    {
        $session = new Session();
        if (!$session->has('glowie.auth.' . $this->guard)) return false;
        $session->remove('glowie.auth.' . $this->guard);
        self::$user[$this->guard] = null;
        return true;
    }

    /**
     * Returns the last authentication error registered, or null if not.
     * @return int|null Last authentication error, null if no auth process has been registered.
     */
    public function getError()
    {
        return $this->error;
    }
}
