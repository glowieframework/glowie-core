<?php
    namespace Glowie\Core\Tools;

    use Glowie\Core\Http\Session;
    use Glowie\Core\Http\Cookies;
    use Exception;
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
     */
    class Authenticator{

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
         * Current authenticated user.
         * @var Model|null
         */
        private static $user = null;

        /**
         * Authenticates an user from the database and store its data in the session.
         * @param string $user Username to authenticate.
         * @param string $password Password to authenticate.
         * @param array $conditions (Optional) Associative array of aditional fields to use while searching for the user.
         * @return bool Returns true if authentication is successful, false otherwise.
         */
        public function login(string $user, string $password, array $conditions = []){
            // Check for empty login credentials
            if(Util::isEmpty($user) || Util::isEmpty($password)){
                $this->error = self::ERR_EMPTY_DATA;
                self::$user = null;
                return false;
            }

            // Create model instance
            $model = Config::get('auth.model');
            if(!$model || !class_exists($model)) throw new Exception("Authenticator: \"{$model}\" was not found");
            $model = new $model;

            // Get auth fields
            $userField = Config::get('auth.user_field', 'email');
            $passwordField = Config::get('auth.password_field', 'password');

            // Fetch user information
            $user = $model->findAndFillBy([$userField => $user, ...$conditions]);
            if(!$user){
                $this->error = self::ERR_NO_USER;
                self::$user = null;
                return false;
            }

            // Check password
            if(password_verify($password, $user->get($passwordField))){
                $session = new Session();
                $session->set('glowie.auth', $user->getPrimary());
                self::$user = $user;
                $this->error = self::ERR_AUTH_SUCCESS;
                return true;
            }else{
                $this->error = self::ERR_WRONG_PASSWORD;
                self::$user = null;
                return false;
            }
        }

        /**
         * Checks if an user is authenticated.
         * @return bool True or false.
         */
        public function check(){
            return !is_null($this->getUser());
        }

        /**
         * Gets the current authenticated user.
         * @return Model|null Returns the user Model instance if authenticated, null otherwise.
         */
        public function getUser(){
            // Check for fetched user
            if(self::$user) return self::$user;

            // Get from session
            $session = new Session();
            $id = $session->get('glowie.auth');
            if(is_null($id)) return null;

            // Create model and return
            $model = Config::get('auth.model');
            if(!$model || !class_exists($model)) throw new Exception("Authenticator: \"{$model}\" was not found");
            $model = new $model;
            $user = $model->findAndFill($id);
            self::$user = $user ?: null;
            return $user;
        }

        /**
         * Gets the id (or other primary key value) from the authenticated user.
         * @return mixed Returns the primary key value if authenticated, null otherwise.
         */
        public function getUserId(){
            // Check for fetched user
            if(self::$user) return self::$user->getPrimary();

            // Get from session
            $session = new Session();
            return $session->get('glowie.auth');
        }

        /**
         * Refreshes the authenticated user model from the database using its primary key.
         * @return bool Returns true on success, false otherwise.
         */
        public function refresh(){
            if(!self::$user){
                $user = $this->getUser();
                if(!$user) return false;
            }

            return self::$user->refresh();
        }

        /**
         * Persists the login for a longer time than the default session expiration.
         * @param int $expires (Optional) Expiration time in seconds.
         * @return bool Returns true on success, false if user is not authenticated yet.
         */
        public function remember(int $expires = Cookies::EXPIRES_DAY){
            $session = new Session();
            if(!$session->has('glowie.auth')) return false;
            $session->persist($expires);
            return true;
        }

        /**
         * Logout the authenticated user.
         * @return bool Returns true on success, false if user is not authenticated yet.
         */
        public function logout(){
            $session = new Session();
            if(!$session->has('glowie.auth')) return false;
            $session->remove('glowie.auth');
            self::$user = null;
            return true;
        }

        /**
         * Returns the last authentication error registered, or null if not.
         * @return int|null Last authentication error, null if no auth process has been registered.
         */
        public function getError(){
            return $this->error;
        }

    }

?>