<?php
    namespace Glowie\Core\Exception;

    use Exception;
    use Throwable;

    /**
     * Routing exception handler for Glowie application.
     * @category Exception
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.0
     */
    class RoutingException extends Exception{

        /**
         * Creates a new instance of RoutingException.
         * @param string $message (Optional) The exception message.
         * @param int $code (Optional) The exception code.
         * @param null|Throwable $previous (Optional) Previous throwable used for exception chaining.
         */
        public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null){
            parent::__construct('Rails: ' . $message, $code, $previous);
        }

    }

?>