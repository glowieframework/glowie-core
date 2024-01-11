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
     * @link https://eugabrielsilva.tk/glowie
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
                return false;
            }

            // Check password
            if(password_verify($password, $user->get($passwordField))){
                $session = new Session();
                $session->set('glowie.auth', $user);
                $this->error = self::ERR_AUTH_SUCCESS;
                return true;
            }else{
                $this->error = self::ERR_WRONG_PASSWORD;
                return false;
            }
        }

        /**
         * Checks if an user is authenticated.
         * @return bool True or false.
         */
        public function check(){
            $session = new Session();
            return $session->has('glowie.auth');
        }

        /**
         * Gets the current authenticated user.
         * @return Model|null Returns the user Model instance if authenticated, null otherwise.
         */
        public function getUser(){
            $session = new Session();
            return $session->get('glowie.auth');
        }

        /**
         * Gets the id (or other primary key value) from the authenticated user.
         * @return mixed Returns the primary key value if authenticated, null otherwise.
         */
        public function getUserId(){
            $session = new Session();
            $user = $session->get('glowie.auth');
            if(!$user) return null;
            return $user->getPrimay();
        }

        /**
         * Refreshes the authenticated user model from the database using its primary key.
         * @return bool Returns true on success, false otherwise.
         */
        public function refresh(){
            $session = new Session();
            $user = $session->get('glowie.auth');
            if(!$user || !$user->refresh()) return false;
            $session->set('glowie.auth', $user);
            return true;
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
            if(!isset($_SESSION['glowie']['auth'])) return false;
            unset($_SESSION['glowie']['auth']);
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