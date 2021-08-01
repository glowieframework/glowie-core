<?php
    namespace Glowie\Core\Exception;

    use Exception;
    use Throwable;

    /**
     * Console exception handler for Glowie application.
     * @category Exception
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.0
     */
    class ConsoleException extends Exception{

        /**
         * Command that triggered the error.
         * @var string
         */
        private $command;

        /**
         * Arguments used in the command.
         * @var array
         */
        private $args;

        /**
         * Creates a new instance of ConsoleException.
         * @param string $command Command that triggered the error.
         * @param array $args Array of arguments used in the command.
         * @param string $message (Optional) The exception message.
         * @param int $code (Optional) The exception code.
         * @param null|Throwable $previous (Optional) Previous throwable used for exception chaining.
         */
        public function __construct(string $command, array $args, string $message = "", int $code = 0, ?Throwable $previous = null){
            parent::__construct('Firefly: ' . $message, $code, $previous);
            $this->command = $command;
            $this->args = $args;
        }

        /**
         * Gets the command that triggered the error.
         * @return string Console command.
         */
        public function getCommand(){
            return $this->command;
        }

        /**
         * Gets the arguments used in the command.
         * @return array Console args.
         */
        public function getArgs(){
            return $this->args;
        }

    }

?>