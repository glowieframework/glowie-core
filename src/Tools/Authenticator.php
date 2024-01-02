<?php
    namespace Glowie\Core\Tools;

    use Glowie\Core\Http\Session;
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
         * Authenticates an user from the database and store its data in the session.
         * @param string $user Username to authenticate.
         * @param string $password Password to authenticate.
         * @param array $conditions (Optional) Associative array of aditional fields to use while searching for the user.
         * @return bool Returns true if authentication is successful, false otherwise.
         */
        public function login(string $user, string $password, array $conditions = []){
            // Check for empty login credentials
            if(Util::isEmpty($user) || Util::isEmpty($password)) return false;

            // Create model instance
            $model = Config::get('auth.model');
            if(!$model || !class_exists($model)) throw new Exception("Authenticator: \"{$model}\" was not found");
            $model = new $model;

            // Get auth fields
            $userField = Config::get('auth.user_field', 'email');
            $passwordField = Config::get('auth.password_field');

            // Fetch user information
            $user = $model->findAndFillBy([$userField => $user, ...$conditions]);
            if(!$user) return false;

            // Check password
            if(password_verify($password, $user->get($passwordField))){
                $session = new Session();
                $session->set('glowie.auth', $user);
                return true;
            }else{
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
         * Refreshes the authenticated user model from the database.
         * @return bool Returns true on success, false otherwise.
         */
        public function refresh(){
            $session = new Session();
            $user = $session->get('glowie.auth');
            if(!$user) return false;
            return $user->refill();
        }

        /**
         * Logout the authenticated user.
         * @return bool Returns true on success, false if user is not authenticated yet.
         */
        public function logout(){
            $session = new Session();
            if(!$session->has('glowie.auth')) return false;
            $session->remove('glowie.auth');
            return true;
        }

    }

?>