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
     * @link https://eugabrielsilva.tk/glowie
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
         * JWT hashing methods.
         * @var array
         */
        private const METHODS = [
            'HS256' => 'sha256',
            'HS384' => 'sha384',
            'HS512' => 'sha512'
        ];

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
         * @return string|null Returns the token if exists or null if there is none.
         */
        public function getToken(){
            return Rails::getRequest()->get('token');
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
         * @return Element|null Returns the JWT payload as an Element or null if the token is invalid.
         */
        public function decodeJwt(string $token, bool $validate = true){
            // Validate data
            if(empty($token)) return null;

            // Split token into parts
            $parsed = explode('.', $token);
            if(count($parsed) !== 3) return null;

            // Validate the header
            $header = json_decode($this->base64UrlDecode($parsed[0]), true);
            if(empty($header['typ']) || $header['typ'] !== 'JWT') return null;

            // Validate the signature and expiration time
            if($validate && !$this->validateJwt($token)) return null;

            // Decode the payload
            $payload = json_decode($this->base64UrlDecode($parsed[1]), true);
            if(!$payload) return null;
            return new Element($payload);
        }

        /**
         * Validates a JSON Web Token using your application secret keys.
         * @param $token Token to validate.
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
            if(empty($header['typ']) || $header['typ'] !== 'JWT' || empty($header['alg']) || !isset(self::METHODS[strtoupper($header['alg'])])) return false;
            $alg = strtoupper($header['alg']);

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