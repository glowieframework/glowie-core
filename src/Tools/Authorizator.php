<?php
    namespace Glowie\Core\Tools;

    use Glowie\Core\Http\Rails;
    use Glowie\Core\Element;
    use Exception;
    use Util;
    use Config;

    /**
     * Token auth handler for Glowie application.
     * @category Authorizator
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) Glowie
     * @license MIT
     * @link https://gabrielsilva.dev.br/glowie
     */
    class Authorizator{

        /**
         * Expiration time for **1 minute**.
         * @var int
         */
        public const EXPIRES_MINUTE = 60;

        /**
         * Expiration time for **1 hour**.
         * @var int
         */
        public const EXPIRES_HOUR = 3600;

        /**
         * Expiration time for **1 day**.
         * @var int
         */
        public const EXPIRES_DAY = 86400;

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
         * Invalid token error code.
         * @var int
         */
        public const ERR_INVALID_TOKEN = 4;

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
         * JWT hashing methods.
         * @var array
         */
        private const METHODS = [
            'HS256' => 'sha256',
            'HS384' => 'sha384',
            'HS512' => 'sha512'
        ];

        /**
         * Creates an Authorizator instance.
         * @param string $guard (Optional) Authentication guard name (from your app configuration).
         */
        public function __construct(string $guard = 'default'){
            $this->setGuard($guard);
        }

        /**
         * Sets the authentication guard (from your app configuration).
         * @param string $guard Guard name.
         * @return Authorizator Current Authorizator instance for nested calls.
         */
        public function setGuard(string $guard){
            if(!Config::has('auth.' . $guard)) throw new Exception('Authorizator: Unknown guard "' . $guard . '"');
            $this->guard = $guard;
            return $this;
        }

        /**
         * Gets a bearer token from the request `Authorization` header.
         * @return string|null Returns the token if exists or null if there is none.
         */
        public function getBearer(){
            $value = Rails::getRequest()->getHeader('Authorization');
            if(!$value) return null;
            if(!Util::startsWith($value, 'Bearer ')) return null;
            return substr($value, 7);
        }

        /**
         * Gets the token from the request body.
         * @param string $param (Optional) Name of the token parameter.
         * @return string|null Returns the token if exists or null if there is none.
         */
        public function getToken(string $param = 'token'){
            return Rails::getRequest()->get($param);
        }

        /**
         * Authenticates an user from the database and returns an authentication token.
         * @param string $user Username to authenticate.
         * @param string $password Password to authenticate.
         * @param array $conditions (Optional) Associative array of aditional fields to use while searching for the user.
         * @param int $expires (Optional) Token expiration time in seconds, leave empty for no expiration.
         * @return string|bool Returns the token if authentication is successful, false otherwise.
         */
        public function login(string $user, string $password, array $conditions = [], int $expires = self::EXPIRES_DAY){
            // Check for empty login credentials
            if(Util::isEmpty($user) || Util::isEmpty($password)){
                $this->error = self::ERR_EMPTY_DATA;
                self::$user[$this->guard] = null;
                return false;
            }

            // Create model instance
            $model = Config::get('auth.' . $this->guard . '.model');
            if(!$model || !class_exists($model)) throw new Exception("Authenticator: \"{$model}\" was not found");
            $model = new $model;

            // Get auth fields
            $userField = Config::get('auth.' . $this->guard . '.user_field', 'email');
            $passwordField = Config::get('auth.' . $this->guard . '.password_field', 'password');

            // Fetch user information
            $user = $model->findAndFillBy(array_merge([$userField => $user], $conditions));
            if(!$user){
                $this->error = self::ERR_NO_USER;
                self::$user[$this->guard] = null;
                return false;
            }

            // Check password
            if(password_verify($password, $user->get($passwordField))){
                $this->error = self::ERR_AUTH_SUCCESS;
                self::$user[$this->guard] = $user;
                return $this->generateJwt(['user' => $user->getPrimary()], $expires, 'HS256', ['glowie' => 'auth']);
            }else{
                $this->error = self::ERR_WRONG_PASSWORD;
                self::$user[$this->guard] = null;
                return false;
            }
        }

        /**
         * Authorizes a previously generated token.
         * @param string $token Token to authorize.
         * @return bool Returns true on authentication success, false otherwise.
         */
        public function authorize(string $token){
            // Decode JWT token
            $token = $this->decodeJwt($token, true, ['glowie' => 'auth']);
            if(!$token || Util::isEmpty($token->user ?? '')){
                $this->error = self::ERR_INVALID_TOKEN;
                self::$user[$this->guard] = null;
                return false;
            }

            // Create model instance
            $model = Config::get('auth.' . $this->guard . '.model');
            if(!$model || !class_exists($model)) throw new Exception("Authenticator: \"{$model}\" was not found");
            $model = new $model;

            // Find user from token
            $user = $model->findAndFill($token->user);
            if(!$user){
                $this->error = self::ERR_NO_USER;
                self::$user[$this->guard] = null;
                return false;
            }

            // Save user and return success
            $this->error = self::ERR_AUTH_SUCCESS;
            self::$user[$this->guard] = $user;
            return true;
        }

        /**
         * Checks if an user is authenticated.
         * @return bool True or false.
         */
        public function check(){
            return isset(self::$user[$this->guard]);
        }

        /**
         * Gets the current authenticated user.
         * @return Model|null Returns the user Model instance if authenticated, null otherwise.
         */
        public function getUser(){
            return self::$user[$this->guard] ?? null;
        }

        /**
         * Gets the id (or other primary key value) from the authenticated user.
         * @return mixed Returns the primary key value if authenticated, null otherwise.
         */
        public function getUserId(){
            $user = self::$user[$this->guard] ?? null;
            if(!$user) return null;
            return $user->getPrimay();
        }

        /**
         * Refreshes the authenticated user model from the database using its primary key.
         * @return bool Returns true on success, false otherwise.
         */
        public function refresh(){
            $user = self::$user[$this->guard] ?? null;
            if(!$user || !$user->refresh()) return false;
            self::$user[$this->guard] = $user;
            return true;
        }

        /**
         * Logout the authenticated user.
         * @return bool Returns true on success, false if user is not authenticated yet.
         */
        public function logout(){
            if(!isset(self::$user[$this->guard])) return false;
            self::$user[$this->guard] = null;
            return true;
        }

        /**
         * Returns the last authentication error registered, or null if not.
         * @return int|null Last authentication error, null if no auth process has been registered.
         */
        public function getError(){
            return $this->error;
        }

        /**
         * Generates a JSON Web Token using your application secret keys.
         * @param Element|array $payload An Element or associative array with the data to store in the token.
         * @param int|null $expires (Optional) Token expiration time in seconds, leave empty for no expiration.
         * @param string $alg (Optional) Hashing algorithm to use in the signature.
         * @param array $headers (Optional) Associative array with additional headers to attach to the token.
         * @return string Returns the token as a string.
         */
        public function generateJwt($payload, ?int $expires = self::EXPIRES_HOUR, string $alg = 'HS256', array $headers = []){
            // Get app key
            $key = Config::get('secret.app_key');
            if(empty($key)) throw new Exception('generateJwt(): Application key was not defined');

            // Parse payload
            if(is_callable([$payload, 'toArray'])) $payload = $payload->toArray();
            if(!empty($expires)) $payload['exp'] = time() + $expires;

            // Generate token
            $alg = strtoupper($alg);
            if(!isset(self::METHODS[$alg])) throw new Exception('generateJwt(): Unsupported hashing algorithm');
            $header = $this->base64UrlEncode(json_encode(array_merge($headers, ['alg' => $alg, 'typ' => 'JWT'])));
            $payload = $this->base64UrlEncode(json_encode($payload));
            $signature = $this->base64UrlEncode(hash_hmac(self::METHODS[$alg], "{$header}.{$payload}", $key, true));
            return "{$header}.{$payload}.{$signature}";
        }

        /**
         * Decodes a JSON Web Token.
         * @param string $token Token to decode.
         * @param bool $validate (Optional) Validate the token signature and expiration time (if available) before decoding.
         * @param array $headers (Optional) Array of additional headers to check.
         * @return Element|null Returns the JWT payload as an Element or null if the token is invalid.
         */
        public function decodeJwt(string $token, bool $validate = true, array $headers = []){
            // Validate data
            if(empty($token)) return null;

            // Split token into parts
            $parsed = explode('.', $token);
            if(count($parsed) !== 3) return null;

            // Validate the header
            $header = json_decode($this->base64UrlDecode($parsed[0]), true);
            if(empty($header['typ']) || $header['typ'] !== 'JWT') return null;

            // Validate the signature and expiration time
            if($validate && !$this->validateJwt($token, $headers)) return null;

            // Decode the payload
            $payload = json_decode($this->base64UrlDecode($parsed[1]), true);
            if(!$payload) return null;
            return new Element($payload);
        }

        /**
         * Validates a JSON Web Token using your application secret keys.
         * @param string $token Token to validate.
         * @param array $headers (Optional) Array of additional headers to check.
         * @return bool Returns true if the token signature is valid, false otherwise.
         */
        public function validateJwt(string $token, array $headers = []){
            // Validate data
            if(empty($token)) return false;

            // Get app key
            $key = Config::get('secret.app_key');
            if(empty($key)) throw new Exception('generateJwt(): Application key was not defined');

            // Split token into parts
            $parsed = explode('.', $token);
            if(count($parsed) !== 3) return false;

            // Validate the header
            $header = json_decode($this->base64UrlDecode($parsed[0]), true);
            if(empty($header['typ']) || $header['typ'] !== 'JWT' || empty($header['alg']) || !isset(self::METHODS[strtoupper($header['alg'])])) return false;
            $alg = strtoupper($header['alg']);

            // Validate custom headers
            if(!empty($headers)){
                foreach($headers as $name => $value){
                    if(!isset($header[$name]) || $header[$name] !== $value) return false;
                }
            }

            // Decode the payload
            $payload = json_decode($this->base64UrlDecode($parsed[1]), true);
            if(!$payload) return false;

            // Validate the expiration time if available
            if(!empty($payload['exp']) && $payload['exp'] < time()) return false;

            // Generate a valid signature to compare
            $signature = $this->base64UrlEncode(hash_hmac(self::METHODS[$alg], "{$parsed[0]}.{$parsed[1]}", $key, true));
            return $signature == $parsed[2];
        }

        /**
         * Encodes a string to base64 URL format.
         * @param string $string String to be encoded.
         * @return string Returns the encoded string.
         */
        private function base64UrlEncode(string $string){
            return rtrim(strtr(base64_encode($string), '+/', '-_'), '=');
        }

        /**
         * Decodes a string in base64 URL format.
         * @param string $string String to be decoded.
         * @return string Returns the decoded string.
         */
        private function base64UrlDecode(string $string){
            return base64_decode(str_pad(strtr($string, '-_', '+/'), strlen($string) % 4, '=', STR_PAD_RIGHT));
        }

    }

?>