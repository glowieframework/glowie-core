<?php
    namespace Glowie\Core\Exception;

    use Exception;
    use Throwable;

    /**
     * SQL query exception handler for Glowie application.
     * @category Exception
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.0
     */
    class QueryException extends Exception{

        /**
         * SQL query that thrown the exception.
         * @var string
         */
        private $query;

        /**
         * Creates a new instance of QueryException.
         * @param string $query SQL query.
         * @param string $message (Optional) The exception message.
         * @param int $code (Optional) The exception code.
         * @param null|Throwable $previous (Optional) Previous throwable used for exception chaining.
         */
        public function __construct(string $query, string $message = "", int $code = 0, ?Throwable $previous = null){
            parent::__construct(sprintf('SQL: [SQL %s] %s (%s)', $code, $message, $query), $code, $previous);
            $this->query = $query;
        }

        /**
         * Gets the SQL query that thrown the exception.
         * @return string Exception SQL query.
         */
        public function getQuery(){
            return $this->query;
        }

    }

?>