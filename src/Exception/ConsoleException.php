<?php
    namespace Glowie\Core\Exception;

    use Exception;
    use Throwable;
    use Glowie\Core\Collection;

    /**
     * Console exception handler for Glowie application.
     * @category Exception
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) Glowie
     * @license MIT
     * @link https://gabrielsilva.dev.br/glowie
     */
    class ConsoleException extends Exception{

        /**
         * Command that thrown the exception.
         * @var string
         */
        private $command;

        /**
         * Arguments used in the command.
         * @var Collection
         */
        private $args;

        /**
         * Creates a new instance of ConsoleException.
         * @param string $command Command that thrown the exception.
         * @param Collection $args Array of arguments used in the command.
         * @param string $message (Optional) The exception message.
         * @param int $code (Optional) The exception code.
         * @param Throwable|null $previous (Optional) Previous throwable used for exception chaining.
         */
        public function __construct(string $command, Collection $args, string $message = "", int $code = 0, ?Throwable $previous = null){
            parent::__construct('CLI: ' . $message, $code, $previous);
            $this->command = $command;
            $this->args = $args;
        }

        /**
         * Gets the command that thrown the exception.
         * @return string Console command.
         */
        public function getCommand(){
            return $this->command;
        }

        /**
         * Gets the arguments used in the command.
         * @return Collection Console args.
         */
        public function getArgs(){
            return $this->args;
        }

    }

?>