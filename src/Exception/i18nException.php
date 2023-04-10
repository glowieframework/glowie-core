<?php
    namespace Glowie\Core\Exception;

    use Exception;
    use Throwable;

    /**
     * Internationalization exception handler for Glowie application.
     * @category Exception
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) Glowie
     * @license MIT
     * @link https://eugabrielsilva.tk/glowie
     */
    class i18nException extends Exception{

        /**
         * Creates a new instance of i18nException.
         * @param string $message (Optional) The exception message.
         * @param int $code (Optional) The exception code.
         * @param Throwable|null $previous (Optional) Previous throwable used for exception chaining.
         */
        public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null){
            parent::__construct('Internationalization: ' . $message, $code, $previous);
        }

    }

?>