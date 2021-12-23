<?php
    namespace Glowie\Core\Exception;

    use Exception;
    use Throwable;

    /**
     * Unit test exception handler for Glowie application.
     * @category Exception
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.1
     */
    class TestException extends Exception{

        /**
         * Creates a new instance of TestException.
         * @param string $message (Optional) The exception message.
         * @param int $code (Optional) The exception code.
         * @param Throwable|null $previous (Optional) Previous throwable used for exception chaining.
         */
        public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null){
            parent::__construct('UnitTest: ' . $message, $code, $previous);
        }

    }

?>