<?php
    namespace Glowie\Core\Traits;

    use Exception;
    use Throwable;
    use mysqli;
    use stdClass;
    use Closure;
    use mysqli_sql_exception;
    use mysqli_result;
    use Config;
    use Glowie\Core\Exception\QueryException;
    use Glowie\Core\Element;
    use Glowie\Core\Exception\DatabaseException;

    /**
     * Database handler trait for Glowie application.
     * @category Database
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) Glowie
     * @license MIT
     * @link https://eugabrielsilva.tk/glowie
     */
    trait DatabaseTrait{

        /**
         * Query instruction.
         * @var string
         */
        private $_instruction;

        /**
         * Stores if there is a transaction running.
         * @var bool
         */
        private $_transaction;

        /**
         * Current working table.
         * @var string
         */
        private $_table;

        /**
         * Current connection name.
         * @var string
         */
        private $_connection;

        /**
         * Database connection handlers.
         * @var array
         */
        public static $_handlers = [];

        /**
         * Raw query.
         * @var string
         */
        private $_raw;

        /**
         * Return results as associative arrays.
         * @var bool
         */
        private $_returnAssoc = false;

        /**
         * Returns next results as associative arrays.
         * @return $this Current instance for nested calls.
         */
        public function asArray(){
            $this->_returnAssoc = true;
            return $this;
        }

        /**
         * Returns next results as Element objects.
         * @return $this Current instance for nested calls.
         */
        public function asElement(){
            $this->_returnAssoc = false;
            return $this;
        }

        /**
         * Sets the working table.
         * @param string $table Table name to set as the working table.
         * @return $this Current instance for nested calls.
         */
        public function table(string $table){
            $this->_table = $table;
            return $this;
        }

        /**
         * Sets the database connection.
         * @param string $database Database connection name (from your app configuration).
         * @return $this Current instance for nested calls.
         * @throws DatabaseException Throws an exception if the connection fails.
         */
        public function database(string $database){
            $this->_connection = $database;
            if(!isset(DatabaseTrait::$_handlers[$this->_connection])) $this->reconnect();
            return $this;
        }

        /**
         * Returns the current database connection handler.
         * @return mysqli The connection object.
         */
        public function getConnection(){
            return DatabaseTrait::$_handlers[$this->_connection];
        }

        /**
         * Refreshes the database connection.
         */
        public function reconnect(){
            // Checks for mysqli extension
            if(!extension_loaded('mysqli')) throw new Exception('DB: Missing "mysqli" extension in your PHP installation');

            // Gets connection configuration
            $database = Config::get("database.{$this->_connection}");
            if(!$database) throw new DatabaseException([], 'Database connection setting "' . $this->_connection . '" not found in your app configuration');

            // Validate settings
            if (empty($database['host'])) throw new DatabaseException($database, 'Database connection "' . $this->_connection . '" host not defined');
            if (empty($database['username'])) throw new DatabaseException($database, 'Database connection "' . $this->_connection . '" username not defined');
            if (empty($database['db'])) throw new DatabaseException($database, 'Database connection "' . $this->_connection . '" name not defined');
            if (empty($database['port'])) $database['port'] = 3306;
            if (empty($database['charset'])) $database['charset'] = 'utf8';

            // Saves the database connection
            try {
                // Creates the connection
                DatabaseTrait::$_handlers[$this->_connection] = new mysqli($database['host'], $database['username'], $database['password'], $database['db'], $database['port']);

                // Sets the charset
                $this->getConnection()->set_charset($database['charset']);

                // Sets the strict mode
                if(!empty($database['strict'])){
                    $this->query('SET SESSION sql_mode="ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION"');
                }else{
                    $this->query('SET SESSION sql_mode="ALLOW_INVALID_DATES,NO_ENGINE_SUBSTITUTION"');
                }
            } catch (Throwable $e) {
                throw new DatabaseException($database, $e->getMessage(), $e->getCode(), $e);
            }
        }

        /**
         * Escapes special characters in a string, preventing SQL injections.
         * @param mixed $string String to escape.
         * @return string Escaped string.
         */
        public function escape($string){
            return $this->getConnection()->escape_string((string)$string);
        }

        /**
         * Returns a value that will not be escaped or quoted into the query.
         * @param mixed $value Value to be returned.
         * @return stdClass Value representation as a generic object.
         */
        public static function raw($value){
            $obj = new stdClass();
            $obj->value = $value;
            return $obj;
        }

        /**
         * Runs a raw SQL query.
         * @param string $query Full raw query to run.
         * @param bool $return (Optional) Set to **true** if the query should return any results.
         * @return array|bool If the query is successful and should return results, will return an array with\
         * the results. Otherwise returns true on success or false on failure.
         * @throws QueryException Throws an exception if the query fails.
         */
        public function query(string $query, bool $return = false){
            $this->_raw = $query;
            return $this->execute($return);
        }

        /**
         * Begins an SQL transaction for the next queries.
         * @return bool Returns true on success or false on failure.
         * @throws Exception Throws an exception if a pending transaction is already running.
         */
        public function beginTransaction(){
            if($this->_transaction) throw new Exception('DB: There is a pending transaction already running');
            $this->_transaction = true;
            return $this->getConnection()->begin_transaction();
        }

        /**
         * Commits the current SQL transaction.
         * @return bool Returns true on success or false on failure.
         * @throws Exception Throws an exception if there is not a running transaction.
         */
        public function commit(){
            if(!$this->_transaction) throw new Exception('DB: There is not a running transaction');
            $this->_transaction = false;
            return $this->getConnection()->commit();
        }

        /**
         * Rolls back the current SQL transaction.
         * @return bool Returns true on success or false on failure.
         * @throws Exception Throws an exception if there is not a running transaction.
         */
        public function rollback(){
            if(!$this->_transaction) throw new Exception('DB: There is not a running transaction');
            $this->_transaction = false;
            return $this->getConnection()->rollback();
        }

        /**
         * Enclosures a set of operations in a transaction.
         * @param Closure $operations Set of operations to run inside the transaction.
         * @return bool Returns true on success or false on failure.
         * @throws QueryException Throws an exception if the query fails.
         */
        public function transaction(Closure $operations){
            // Begins the transaction
            $this->beginTransaction();

            try {
                // Run operations
                call_user_func_array($operations, [$this]);
            } catch (Throwable $e) {
                // If something fails, rolls back the transaction
                return $this->rollback();
            }

            // Commits the transaction if nothing failed
            return $this->commit();
        }

        /**
         * Disables FOREIGN KEY checks for the current session.
         * @return bool Returns true on success or false on failure.
         * @throws QueryException Throws an exception if the query fails.
         */
        public function disableFkChecks(){
            return $this->query('SET FOREIGN_KEY_CHECKS = 0');
        }

        /**
         * Enables FOREIGN KEY checks for the current session.
         * @return bool Returns true on success or false on failure.
         * @throws QueryException Throws an exception if the query fails.
         */
        public function enableFkChecks(){
            return $this->query('SET FOREIGN_KEY_CHECKS = 1');
        }

        /**
         * Runs the current built query.
         * @param bool $returns (Optional) If the query should return a result.
         * @param bool $returnsFirst (Optional) If the query should return a single result.
         * @return mixed If the query is successful and should return any results, will return an Element/associative array with the\
         * first result or an array of results. Otherwise returns true on success or false on failure.
         * @throws QueryException Throws an exception if the query fails.
         */
        private function execute(bool $returns = false, bool $returnsFirst = false){
            try {
                // Run query and clear its data
                $built = $this->getQuery();
                $query = $this->getConnection()->query($built);
                $this->clearQuery();

                // Checks for query result
                if ($query !== false) {
                    $result = true;

                    // Checks for return type
                    if($returns && $query instanceof mysqli_result){
                        if($returnsFirst){
                            // Returns only first row
                            $result = null;
                            if ($query->num_rows > 0) {
                                $row = $query->fetch_assoc();
                                $query->close();
                                $result = $this->_returnAssoc ? $row : new Element($row);
                            }
                        }else{
                            // Returns all rows
                            $result = [];
                            if ($query->num_rows > 0) {
                                $rows = $query->fetch_all(MYSQLI_ASSOC);
                                $query->close();
                                if($this->_returnAssoc){
                                    $result = $rows;
                                }else{
                                    foreach ($rows as $row) $result[] = new Element($row);
                                }
                            }
                        }
                    }

                    // Stores the last insert ID and returns the result
                    $this->_lastInsertId = $this->getConnection()->insert_id;
                    return $result;
                }else{
                    // Query failed
                    return false;
                }
            } catch (mysqli_sql_exception $e) {
                // Query failed with error
                throw new QueryException($built, $e->getMessage(), $e->getCode(), $e);
            }
        }

    }

?>