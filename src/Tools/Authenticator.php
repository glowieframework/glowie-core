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
     * @version 1.1
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
         * @return string Returns the JWT token as a string.
         */
        public function generateJwt(array $payload, string $alg = 'HS256'){
            // Get app key
            $key = Config::get('secret.app_key');
            if(empty($key)) throw new Exception('generateJwt(): Application key was not defined');

            // Generate token
            if(!isset(self::METHODS[$alg])) throw new Exception('generateJwt(): Invalid hashing algorithm');
            $method = self::METHODS[$alg];
            $header = $this->base64UrlEncode(json_encode(['alg' => $alg, 'typ' => 'JWT']));
            $payload = $this->base64UrlEncode(json_encode($payload));
            $signature = $this->base64UrlEncode(hash_hmac($method, "{$header}.{$payload}", $key, true));
            return "{$header}.{$payload}.{$signature}";
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