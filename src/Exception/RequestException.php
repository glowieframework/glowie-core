<?php
    namespace Glowie\Core\Exception;

    use Exception;
    use Throwable;

    /**
     * Request exception handler for Glowie application.
     * @category Exception
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.2
     */
    class RequestException extends Exception{

        /**
         * Requested URL that thrown the exception.
         * @var string
         */
        private $url;

        /**
         * Creates a new instance of RequestException.
         * @param string $message (Optional) The exception message.
         * @param int $code (Optional) The exception code.
         * @param Throwable|null $previous (Optional) Previous throwable used for exception chaining.
         */
        public function __construct(string $url, string $message = "", int $code = 0, ?Throwable $previous = null){
            parent::__construct('Request: ' . $message, $code, $previous);
            $this->url = $url;
        }

        /**
         * Gets the requested URL that thrown the exception.
         * @return string Exception URL.
         */
        public function getURL(){
            return $this->url;
        }

    }

?>