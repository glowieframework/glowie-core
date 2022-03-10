<?php
    namespace Glowie\Core\Tools;

    use Glowie\Core\Http\Rails;
    use Glowie\Core\Element;
    use Exception;
    use Util;
    use Config;

    /**
     * Auth handler for Glowie application.
     * @category Authenticator
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.2
     */
    class Authenticator{

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
         * Gets a basic `Authorization` header.
         * @return Element|null Returns an Element with the username and password if exists or null if there is none.
         */
        public function getBasic(){
            $value = Rails::getRequest()->getHeader('Authorization');
            if(!$value) return null;
            if(!Util::startsWith($value, 'Basic ')) return null;
            $value = base64_decode(substr($value, 6));
            if(!$value) return null;
            $value = explode(':', $value, 2);
            return new Element(['username' => $value[0] ?? null, 'password' => $value[1] ?? null]);
        }

        /**
         * Gets a bearer token from the `Authorization` header.
         * @return string|null Returns the token if exists or null if there is none.
         */
        public function getBearer(){
            $value = Rails::getRequest()->getHeader('Authorization');
            if(!$value) return null;
            if(!Util::startsWith($value, 'Bearer ')) return null;
            return substr($value, 7);
        }

        /**
         * Generates a JSON Web Token using your application secret keys.
         * @param array $payload Data to store in the token.
         * @param string $alg (Optional) Hashing algorithm to use in the signature.
         * @param array $headers (Optional) Additional headers to attach to the token.
         * @return string Returns the JWT token as a string.
         */
        public function generateJwt(array $payload, string $alg = 'HS256', array $headers = []){
            // Get app key
            $key = Config::get('secret.app_key');
            if(empty($key)) throw new Exception('generateJwt(): Application key was not defined');

            // Generate token
            if(!isset(self::METHODS[$alg])) throw new Exception('generateJwt(): Unsupported hashing algorithm');
            $header = $this->base64UrlEncode(json_encode(array_merge($headers, ['alg' => $alg, 'typ' => 'JWT'])));
            $payload = $this->base64UrlEncode(json_encode($payload));
            $signature = $this->base64UrlEncode(hash_hmac(self::METHODS[$alg], "{$header}.{$payload}", $key, true));
            return "{$header}.{$payload}.{$signature}";
        }

        /**
         * Decodes a JSON Web Token.
         * @param string $token JWT token to decode.
         * @param bool $validate (Optional) Validate the token signature before decoding.
         * @return array|null Returns the JWT payload as an associative array or null if the token is invalid.
         */
        public function decodeJwt(string $token, bool $validate = true){
            // Validate data
            if(empty($token)) return null;

            // Split token into parts
            $token = explode('.', $token);
            if(count($token) !== 3) return null;

            // Validate the header
            $header = json_decode($this->base64UrlDecode($token[0]), true);
            if(empty($header['typ']) || $header['typ'] !== 'JWT') return null;

            // Validate the signature
            if($validate && !$this->validateJwt($token)) return null;

            // Decode the payload
            $payload = json_decode($this->base64UrlDecode($token[1]), true);
            return $payload ?? [];
        }

        /**
         * Validates a JSON Web Token using your application secret keys.
         * @param $token JWT token to validate.
         * @return bool Returns true if the token signature is valid, false otherwise.
         */
        public function validateJwt(string $token){
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
            if(empty($header['typ']) || $header['typ'] !== 'JWT' || empty($header['alg']) || !isset(self::METHODS[$header['alg']])) return false;

            // Generate a valid token to compare the signature
            $payload = json_decode($this->base64UrlDecode($parsed[1]), true);
            $validToken = $this->generateJwt($payload ?? [], $header['alg'], $header);
            return $validToken == $token;
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