<?php
    namespace Glowie\Core;
    
    use mysqli;
    use Closure;
    use mysqli_sql_exception;
    use stdClass;

    /**
     * Database ORM toolkit for Glowie application.
     * @category Database
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.0
     */
    class Kraken extends Element{
        
        /**
         * Current connection settings.
         * @var array
         */
        private $_database;

        /**
         * Current default table.
         * @var string
         */
        private $_table;

        /**
         * Current connection handler.
         * @var mysqli
         */
        private $_connection;

        /**
         * Global connection handler.
         * @var mysqli
         */
        private static $_global;

        /**
         * Enable transactions.
         * @var bool
         */
        private $_transactions;

        /**
         * Query instruction.
         * @var string
         */
        private $_instruction;

        /**
         * SELECT statement.
         * @var string
         */
        private $_select;

        /**
         * FROM statement.
         * @var string
         */
        private $_from;

        /**
         * JOIN statements.
         * @var string[]
         */
        private $_join;

        /**
         * WHERE statements.
         * @var string[]
         */
        private $_where;

        /**
         * GROUP BY statement.
         * @var string
         */
        private $_group;

        /**
         * ORDER BY statements.
         * @var string[]
         */
        private $_order;

        /**
         * LIMIT statement.
         * @var string
         */
        private $_limit;

        /**
         * INSERT fields.
         * @var string
         */
        private $_insert;

        /**
         * INTO statement.
         * @var string
         */
        private $_into;

        /**
         * VALUES statement.
         * @var string
         */
        private $_values;

        /**
         * UPDATE table.
         * @var string
         */
        private $_update;

        /**
         * SET statement.
         * @var string
         */
        private $_set;

        /**
         * Raw query.
         * @var string
         */
        private $_raw;

        /**
         * Creates a new database connection.
         * @param string $table (Optional) Table name to set as default.
         * @param array $database (Optional) Associative array with the connection settings.\
         * Use an empty array to connect to the globally defined database (in **app/config/Config.php**).
         * @param bool $transactions Enable or disable database transactions.
         */
        public function __construct(string $table = 'glowie', array $database = [], bool $transactions = true){
            $this->setDatabase($database);
            $this->setTable($table);
            $this->setTransactions($transactions);
        }

        /**
         * Sets the database connection settings.
         * @param array $database Associative array with the connection settings.\
         * Use an empty array to connect to the globally defined database (in **app/Config.php**).
         * @return Kraken Current Kraken instance for nested calls.
         */
        public function setDatabase(array $database){
            // Checks for the global database setting
            $global = empty($database);
            if ($global) $database = GLOWIE_CONFIG['database'];

            // Validate settings
            if (!is_array($database)) trigger_error('setDatabase: Database connection settings must be an array');
            if (empty($database['host'])) trigger_error('setDatabase:  Database host not defined');
            if (empty($database['username'])) trigger_error('setDatabase:  Database username not defined');
            if (empty($database['db'])) trigger_error('setDatabase: Database name not defined');
            if (empty($database['port'])) $database['port'] = 3306;
            
            // Saves the database connection
            $this->_database = $database;
            if($global){
                // Checks if the global database is already connected
                if(!empty(self::$_global)){
                    $connection = self::$_global;
                }else{
                    $connection = new mysqli($database['host'], $database['username'], $database['password'], $database['db'], $database['port']);
                    self::$_global = $connection;
                }
            }else{
                $connection = new mysqli($database['host'], $database['username'], $database['password'], $database['db'], $database['port']);
            }
            $this->_connection = $connection;
            return $this;
        }
        
        /**
         * Sets the default table.
         * @param string $table Table name to set as default.
         * @return Kraken Current Kraken instance for nested calls.
         */
        public function setTable(string $table){
            if (empty($table)) trigger_error('setTable: Table name should not be empty');
            $this->_table = $table;
            return $this;
        }

        /**
         * Returns the current default table.
         * @return string Table name.
         */
        public function getTable(){
            return $this->_table;
        }

        /**
         * Returns the current database connection settings.
         * @return array Associative array with the connection settings.
         */
        public function getDatabase(){
            return $this->_database;
        }

        /**
         * Returns the current database connection handler.
         * @return mysqli The connection object.
         */
        public function getConnection(){
            return $this->_connection;
        }

        /**
         * Enables or disables database transactions.
         * @param bool $option True or false.
         */
        public function setTransactions(bool $option){
            $this->_transactions = $option;
        }

        /**
         * Escapes special characters in a string, preventing SQL injections.
         * @param string $string String to escape.
         * @return string Escaped string.
         */
        public function escape(string $string){
            return $this->_connection->escape_string($string);
        }

        /**
         * Returns a value that will not be escaped or quoted into the query.
         * @param string $value Value to be returned.
         * @return stdClass Value representation as an object.
         */
        public static function raw(string $value){
            $obj = new stdClass();
            $obj->value = $value;
            return $obj;
        }

        /**
         * Prepares a SELECT query.
         * @param string|string[] $columns (Optional) Columns to select in the query. Can be a single field name or an array of columns.\
         * You can also use a raw SELECT query.
         * @param bool $distinct (Optional) Sets the SELECT query as DISTINCT.
         * @return Kraken Current Kraken instance for nested calls.
         */
        public function select($columns = '*', $distinct = false){
            $this->_instruction = $distinct ? 'SELECT DISTINCT' : 'SELECT';
            if(is_array($columns)){
                $this->_select = implode(', ', $columns);
            }else{
                $this->_select = $columns;
            }
            return $this;
        }

        /**
         * Prepares a SELECT DISTINCT query.
         * @param string|string[] $columns (Optional) Columns to select in the query. Can be a single field name or an array of columns.\
         * You can also use a raw SELECT query.
         * @return Kraken Current Kraken instance for nested calls.
         */
        public function selectDistinct($columns = '*'){
            return $this->select($columns, true);
        }

        /**
         * Sets the query FROM statement.
         * @param string|string[] $table Table name or an array of tables. You can also use a raw FROM query.
         * @return Kraken Current Kraken instance for nested calls.
         */
        public function from($table){
            if(is_array($table)){
                $this->_from = implode(', ', $table);
            }else{
                $this->_from = $table;
            }
            return $this;
        }

        /**
         * Adds a table JOIN in the query.
         * @param string $table Table name to JOIN.
         * @param string $param1 First condition parameter.
         * @param string $param2 If `$param3` isset, the operator used in the condition. Otherwise, the second\
         * condition parameter.
         * @param mixed $param3 (Optional) Second condition parameter if `$param2` is the operator.
         * @param string $type (Optional) JOIN type (INNER, LEFT, RIGHT or FULL).
         * @return Kraken Current Kraken instance for nested calls.
         */
        public function join(string $table, string $param1, string $param2, $param3 = null, string $type = 'INNER'){
            // Checks if the operator was passed
            if($param3 === null){
                $param3 = $param2;
                $param2 = '=';
            }
            $type = strtoupper($type);
            $this->_join[] = "{$type} JOIN {$table} ON {$param1} {$param2} {$param3}";
            return $this;
        }

        /**
         * Adds a raw table JOIN in the query.
         * @param string $join Full JOIN clause.
         * @return Kraken Current Kraken instance for nested calls.
         */
        public function rawJoin(string $join){
            $this->_join[] = $join;
            return $this;
        }

        /**
         * Adds a table INNER JOIN in the query.
         * @param string $table Table name to JOIN.
         * @param string $param1 First condition parameter.
         * @param string $param2 If `$param3` isset, the operator used in the condition. Otherwise, the second\
         * condition parameter.
         * @param mixed $param3 (Optional) Second condition parameter if `$param2` is the operator.
         * @return Kraken Current Kraken instance for nested calls.
         */
        public function innerJoin(string $table, string $param1, string $param2, $param3 = null){
            return $this->join($table, $param1, $param2, $param3);
        }

        /**
         * Adds a table LEFT JOIN in the query.
         * @param string $table Table name to JOIN.
         * @param string $param1 First condition parameter.
         * @param string $param2 If `$param3` isset, the operator used in the condition. Otherwise, the second\
         * condition parameter.
         * @param mixed $param3 (Optional) Second condition parameter if `$param2` is the operator.
         * @return Kraken Current Kraken instance for nested calls.
         */
        public function leftJoin(string $table, string $param1, string $param2, $param3 = null){
            return $this->join($table, $param1, $param2, $param3, 'LEFT');
        }

        /**
         * Adds a table RIGHT JOIN in the query.
         * @param string $table Table name to JOIN.
         * @param string $param1 First condition parameter.
         * @param string $param2 If `$param3` isset, the operator used in the condition. Otherwise, the second\
         * condition parameter.
         * @param mixed $param3 (Optional) Second condition parameter if `$param2` is the operator.
         * @return Kraken Current Kraken instance for nested calls.
         */
        public function rightJoin(string $table, string $param1, string $param2, $param3 = null){
            return $this->join($table, $param1, $param2, $param3, 'RIGHT');
        }

        /**
         * Adds a table FULL JOIN in the query.
         * @param string $table Table name to JOIN.
         * @param string $param1 First condition parameter.
         * @param string $param2 If `$param3` isset, the operator used in the condition. Otherwise, the second\
         * condition parameter.
         * @param mixed $param3 (Optional) Second condition parameter if `$param2` is the operator.
         * @return Kraken Current Kraken instance for nested calls.
         */
        public function fullJoin(string $table, string $param1, string $param2, $param3 = null){
            return $this->join($table, $param1, $param2, $param3, 'FULL');
        }

        /**
         * Adds a WHERE condition to the query.
         * @param mixed $column Column name or grouped WHERE closure.
         * @param mixed $param1 (Optional) If `$param2` isset, the operator used in the condition. Otherwise,\
         * the value to check to.
         * @param mixed $param2 (Optional) Value if `$param1` is the operator.
         * @param string $type (Optional) Chaining type (AND or OR).
         * @return Kraken Current Kraken instance for nested calls.
         */
        public function where($column, $param1 = null, $param2 = null, string $type = 'AND'){
            // Checks for the condition type
            $type = strtoupper($type);
            if(!empty($this->_where)){
                if(end($this->_where) == '('){
                    $query = "";
                }else{
                    $query = "{$type} ";
                }
            }else{
                $query = "";
            }

            // Checks for grouped wheres
            if($column instanceof Closure){
                if(!empty($this->_where) && end($this->_where) != '('){
                    $this->_where[] = "{$type} ";
                    $this->_where[] = "(";
                }else{
                    $this->_where[] = "(";
                }

                call_user_func_array($column, array(&$this));
                $this->_where[] = ')';
                return $this;
            }

            // Checks if the operator was passed
            if($param2 === null){
                $param2 = $param1;
                $param1 = '=';
            }
            
            // Checks operation types
            $param1 = strtoupper($param1);
            if(($param1 == 'BETWEEN' || $param1 == 'NOT BETWEEN') && is_array($param2)){
                $values = [];

                // Escaping values
                foreach($param2 as $value){
                    if($value instanceof stdClass){
                        $values[] = $value->value;
                    }else{
                        $values[] = "\"{$this->escape($value)}\"";
                    }
                }

                $query .= "{$column} {$param1} {$values[0]} AND {$values[1]}";
            }else if(is_array($param2)){
                $values = [];

                // Escaping values
                foreach($param2 as $value){
                    if($value instanceof stdClass){
                        $values[] = $value->value;
                    }else{
                        $values[] = "\"{$this->escape($value)}\"";
                    }
                }
                
                if($param1 == '=') $param1 = 'IN';
                $values = implode(', ', $values);
                $query .= "{$column} {$param1} ($values)";
            }else if($param2 === 'NULL'){
                if($param1 == '=') $param1 = 'IS';
                $query .= "{$column} {$param1} NULL";
            }else{
                // Escaping values
                if($param2 instanceof stdClass){
                    $param2 = $param2->value;
                }else{
                    $param2 = "\"{$this->escape($param2)}\"";
                }

                $query .= "{$column} {$param1} {$param2}";
            }

            $this->_where[] = $query;
            return $this;
        }

        /**
         * Adds an OR WHERE condition to the query.
         * @param string|Closure $column Column name or grouped WHERE closure.
         * @param mixed $param1 (Optional) If `$param2` isset, the operator used in the condition. Otherwise,\
         * the value to check to.
         * @param mixed $param2 (Optional) Value if `$param1` is the operator.
         * @return Kraken Current Kraken instance for nested calls.
         */
        public function orWhere($column, $param1 = null, $param2 = null){
            return $this->where($column, $param1, $param2, 'OR');
        }

        /**
         * Adds a raw WHERE condition to the query.\
         * **Note:** This does not prevent SQL injection attacks.
         * @param string $condition Full WHERE condition.
         * @param string $type (Optional) Chaining type (AND or OR).
         * @return Kraken Current Kraken instance for nested calls.
         */
        public function rawWhere(string $condition, string $type = 'AND'){
            $this->_where[] = (!empty($this->_where) ? "{$type} " : "") . $condition;
            return $this;
        }

        /**
         * Adds a raw OR WHERE condition to the query.\
         * **Note:** This does not prevent SQL injection attacks.
         * @param string $condition Full WHERE condition.
         * @return Kraken Current Kraken instance for nested calls.
         */
        public function orRawWhere(string $condition){
            return $this->rawWhere($condition, 'OR');
        }

        /**
         * Adds a WHERE IN condition to the query.
         * @param string $column Column name.
         * @param array $values Array of values to check to.
         * @return Kraken Current Kraken instance for nested calls.
         */
        public function whereIn(string $column, array $values){
            return $this->where($column, $values);
        }

        /**
         * Adds an OR WHERE IN condition to the query.
         * @param string $column Column name.
         * @param array $values Array of values to check to.
         * @return Kraken Current Kraken instance for nested calls.
         */
        public function orWhereIn(string $column, array $values){
            return $this->where($column, 'IN', $values, 'OR');
        }

        /**
         * Adds a WHERE NOT IN condition to the query.
         * @param string $column Column name.
         * @param array $values Array of values to check to.
         * @return Kraken Current Kraken instance for nested calls.
         */
        public function whereNotIn(string $column, array $values){
            return $this->where($column, 'NOT IN', $values);
        }

        /**
         * Adds an OR WHERE NOT IN condition to the query.
         * @param string $column Column name.
         * @param array $values Array of values to check to.
         * @return Kraken Current Kraken instance for nested calls.
         */
        public function orWhereNotIn(string $column, array $values){
            return $this->where($column, 'NOT IN', $values, 'OR');
        }

        /**
         * Adds a WHERE BETWEEN condition to the query.
         * @param string $column Column name.
         * @param mixed $value1 First value in the range.
         * @param mixed $value2 Last value in the range.
         * @return Kraken Current Kraken instance for nested calls.
         */
        public function whereBetween(string $column, $value1, $value2){
            return $this->where($column, 'BETWEEN', [$value1, $value2]);
        }
        
        /**
         * Adds an OR WHERE BETWEEN condition to the query.
         * @param string $column Column name.
         * @param mixed $value1 First value in the range.
         * @param mixed $value2 Last value in the range.
         * @return Kraken Current Kraken instance for nested calls.
         */
        public function orWhereBetween(string $column, $value1, $value2){
            return $this->where($column, 'BETWEEN', [$value1, $value2], 'OR');
        }
        
        /**
         * Adds a WHERE NOT BETWEEN condition to the query.
         * @param string $column Column name.
         * @param mixed $value1 First value in the range.
         * @param mixed $value2 Last value in the range.
         * @return Kraken Current Kraken instance for nested calls.
         */
        public function whereNotBetween(string $column, $value1, $value2){
            return $this->where($column, 'NOT BETWEEN', [$value1, $value2]);
        }

        /**
         * Adds an OR WHERE NOT BETWEEN condition to the query.
         * @param string $column Column name.
         * @param mixed $value1 First value in the range.
         * @param mixed $value2 Last value in the range.
         * @return Kraken Current Kraken instance for nested calls.
         */
        public function orWhereNotBetween(string $column, $value1, $value2){
            return $this->where($column, 'NOT BETWEEN', [$value1, $value2], 'OR');
        }

        /**
         * Adds a WHERE NULL condition to the query.
         * @param string $column Column name.
         * @return Kraken Current Kraken instance for nested calls.
         */
        public function whereNull(string $column){
            return $this->where($column, 'NULL');
        }

        /**
         * Adds an OR WHERE NULL condition to the query.
         * @param string $column Column name.
         * @return Kraken Current Kraken instance for nested calls.
         */
        public function orWhereNull(string $column){
            return $this->where($column, 'IS', 'NULL', 'OR');
        }
        
        /**
         * Adds a WHERE NOT NULL condition to the query.
         * @param string $column Column name.
         * @return Kraken Current Kraken instance for nested calls.
         */
        public function whereNotNull(string $column){
            return $this->where($column, 'IS NOT', 'NULL');
        }

        /**
         * Adds an OR WHERE NOT NULL condition to the query.
         * @param string $column Column name.
         * @return Kraken Current Kraken instance for nested calls.
         */
        public function orWhereNotNull(string $column){
            return $this->where($column, 'IS NOT', 'NULL', 'OR');
        }

        /**
         * Sets the query GROUP BY statement.
         * @param string|string[] $column Column name or an array of columns.
         * @return Kraken Current Kraken instance for nested calls.
         */
        public function groupBy($column){
            if(is_array($column)){
                $this->_group = implode(', ', $column);
            }else{
                $this->_group = $column;
            }
            return $this;
        }

        /**
         * Sets a raw GROUP BY statement to the query.
         * @param string $statement Full GROUP BY statement.
         * @return Kraken Current Kraken instance for nested calls.
         */
        public function rawGroupBy(string $statement){
            $this->_group = $statement;
            return $this;
        }

        /**
         * Adds an ORDER BY statement to the query.
         * @param string $column Column name.
         * @param string $direction (Optional) Sorting direction (ASC or DESC).
         * @return Kraken Current Kraken instance for nested calls.
         */
        public function orderBy($column, string $direction = 'asc'){
            $direction = strtoupper($direction);
            $this->_order[] = "{$column} {$direction}";
            return $this;
        }

        /**
         * Adds a random ORDER BY statement to the query.
         * @return Kraken Current Kraken instance for nested calls.
         */
        public function orderByRandom(){
            $this->_order[] = "RAND()";
            return $this;
        }
        
        /**
         * Adds a raw ORDER BY statement to the query.
         * @param string $statement Full ORDER BY statement.
         * @return Kraken Current Kraken instance for nested calls.
         */
        public function rawOrderBy(string $statement){
            $this->_order[] = $statement;
            return $this;
        }

        /**
         * Sets the query LIMIT statement.
         * @param int $param1 If `$param2` isset, the offset setting. Otherwise, the limit setting.
         * @param int $param2 (Optional) Limit setting if `$param1` is the offset.
         * @return Kraken Current Kraken instance for nested calls.
         */
        public function limit(int $param1, int $param2 = null){
            if($param2 === null){
                $this->_limit = "0, {$param1}";
            }else{
                $this->_limit = "{$param1}, {$param2}";
            }
            return $this;
        }

        /**
         * Fetches the first result from a SELECT query.
         * @return Element|bool Returns the first resulting row on success or false on errors.
         */
        public function fetchRow(){
            return $this->execute(true, true);
        }
        
        /**
         * Fetches all results from a SELECT query.
         * @return array|bool Returns an array with all resulting rows on success or false on errors.
         */
        public function fetchAll(){
            return $this->execute(true);
        }

        /**
         * Inserts data into the table.
         * @param string|array $param1 If `$param2` isset, the table to insert data. Otherwise, an associative array\
         * relating fields and values to insert. Also accepts an array of multiple inserts.
         * @param array $param2 (Optional) Array with data to insert if `$param1` is the table.
         * @param bool $ignore (Optional) Ignore failing rows while inserting data (INSERT IGNORE).
         * @return bool Returns true on success or false on errors.
         */
        public function insert($param1, $param2 = null, $ignore = false){
            // Checks if the table was passed
            if($param2 === null){
                $param2 = $param1;
                $param1 = $this->_table;
            }

            // Prepares instruction
            $type = $ignore ? ' IGNORE' : '';
            $this->_instruction = "INSERT{$type}";
            $this->_into = $param1;
            $fields = [];
            $values = [];
            
            // Checks for multiple inserts
            if(isset($param2[0]) && is_array($param2[0])){
                // Get fields
                foreach($param2[0] as $field => $value){
                    $fields[] = $field;
                }
                
                // Get values
                foreach($param2 as $row){
                    $result = [];

                    // Escape values
                    foreach($row as $value){
                        if($value instanceof stdClass){
                            $result[] = $value->value;
                        }else{
                            $result[] = "\"{$this->escape($value)}\"";
                        }
                    }
                    $result = implode(', ', $result);
                    $values[] = "({$result})";
                }
            }else{
               foreach($param2 as $field => $value){
                  $fields[] = $field;
                  
                  // Escape values
                  if($value instanceof stdClass){
                    $values[] = $value->value;
                  }else{
                    $values[] = "\"{$this->escape($value)}\"";
                  }
               }
               $values = implode(', ', $values);
               $values = ["({$values})"];
            }
            
            // Stores data to the query builder and run
            $this->_values = implode(', ', $values);
            $this->_insert = implode(', ', $fields);
            return $this->execute();
        }

        /**
         * Inserts data into the table ignoring failing rows.
         * @param string|array $param1 If `$param2` isset, the table to insert data. Otherwise, an associative array\
         * relating fields and values to insert. Also accepts an array of multiple inserts.
         * @param array $param2 (Optional) Array with data to insert if `$param1` is the table.
         * @return bool Returns true on success or false on errors.
         */
        public function insertIgnore($param1, $param2 = null){
            return $this->insert($param1, $param2, true);
        }

        /**
         * Updates data in the table. **Do not forget to use WHERE statements\
         * before calling this function.**
         * @param string|array $param1 If `$param2` isset, the table to update data. Otherwise, an associative array\
         * relating fields and values to update.
         * @param array $param2 (Optional) Array with data to update if `$param1` is the table.
         * @return bool Returns true on success or false on errors.
         */
        public function update($param1, $param2 = null){
            // Checks if the table was passed
            if($param2 === null){
                $param2 = $param1;
                $param1 = $this->_table;
            }

            // Set params
            $this->_instruction = 'UPDATE';
            $this->_update = $param1;
            $set = [];

            // Escape values
            foreach($param2 as $key => $value){
                if($value instanceof stdClass){
                    $set[] = "{$key} = {$value->value}";
                }else{
                    $set[] = "{$key} = \"{$this->escape($value)}\"";
                }
            }
            
            $this->_set = implode(', ', $set);
            return $this->execute();
        }

        /**
         * Deletes data from the table. **Do not forget to use WHERE statements\
         * before calling this function.**
         * @param string $table (Optional) Table name to delete from.
         * @return bool Returns true on success or false on errors.
         */
        public function delete(string $table = null){
            $this->_instruction = 'DELETE';
            if(!empty($table)) $this->_from = $table;
            return $this->execute();
        }

        /**
         * Counts the number of resulting rows from a SELECT query.
         * @param string $column (Optional) Column to use as the counting base. Using * will count all rows including `NULL` values.\
         * Setting a column name will count all rows excluding `NULL` values from that column.
         * @return int|bool Returns the number of rows on success or false on errors.
         */
        public function count(string $column = '*'){
            // Saves current query state
            $query = $this->backupQuery();

            // Count rows
            $this->_instruction = "SELECT";
            $this->_select = "COUNT({$column}) AS count";
            $result = $this->execute(true, true);

            // Rollback query state
            $this->restoreQuery($query);

            if($result !== false){
                return (int)$result->count;
            }else{
                return $result;
            }
        }

        /**
         * Checks if there are any records that matches a SELECT query.
         * @return bool Returns true if exists or false if not.
         */
        public function exists(){
            $result = $this->count();
            return (is_int($result) && $result >= 1);
        }

        /**
         * Fetches all results from a SELECT query with pagination.
         * @param int $currentPage Current page to get results.
         * @param int $resultsPerPage (Optional) Number of results to get per page.
         * @return Element Returns an object with the pagination result.
         */
        public function paginate(int $currentPage, int $resultsPerPage = 25){
            // Counts total pages
            $totalResults = $this->count();
            $totalPages = floor($totalResults / $resultsPerPage);
            if($totalResults % $resultsPerPage != 0) $totalPages++;

            // Gets paginated results
            $this->limit(($currentPage - 1) * $resultsPerPage, $resultsPerPage);
            $results = $this->execute(true);

            // Parse results
            return new Element([
                'current_page' => $currentPage,
                'total_pages' => $totalPages,
                'results_per_page' => $resultsPerPage,
                'total_results' => $totalResults,
                'results' => $results
            ]);
        }

        /**
         * Returns the last inserted `AUTO_INCREMENT` value from an INSERT query.
         * @return mixed Last insert id.
         */
        public function lastInsertId(){
            return $this->_connection->insert_id;
        }

        /**
         * Returns the number of affected rows from an UPDATE or INSERT query.
         * @return int Number of affected rows.
         */
        public function affectedRows(){
            return $this->_connection->affected_rows;
        }

        /**
         * Runs a raw SQL query.
         * @param string $query Full raw query to run.
         * @param bool $return (Optional) Set to **true** if the query should return any results.
         * @return array|bool If the query is successful and should return results, will return an array with\
         * the results. Otherwise returns true on success or false on errors.
         */
        public function query(string $query, bool $return = false){
            $this->_raw = $query;
            return $this->execute($return);
        }

        /**
         * Clears the current built query entirely.
         */
        public function clearQuery(){
            $this->_instruction = '';
            $this->_select = '';
            $this->_from = '';
            $this->_into = '';
            $this->_join = [];
            $this->_where = [];
            $this->_group = '';
            $this->_order = [];
            $this->_limit = '';
            $this->_insert = '';
            $this->_into = '';
            $this->_values = '';
            $this->_update = '';
            $this->_set = '';
            $this->_raw = '';
        }

        /**
         * Returns the current built query.
         * @return string Current built query.
         */
        private function getQuery(){
            // Checks for raw query
            if(!empty($this->_raw)) return $this->_raw;

            // Checks for empty query
            if(empty($this->_instruction)) $this->_instruction = 'SELECT';
            if(empty($this->_select)) $this->_select = '*';
            
            // Gets the instruction
            $query = $this->_instruction;

            // Gets SELECT statement
            if($this->_instruction == 'SELECT' || $this->_instruction == 'SELECT DISTINCT'){
                $query .= " {$this->_select}";
            }

            // Gets FROM statement
            if($this->_instruction == 'SELECT' || $this->_instruction == 'SELECT DISTINCT' || $this->_instruction == 'DELETE'){
                if (!empty($this->_from)) {
                    $query .= " FROM {$this->_from}";
                } else {
                    $query .= " FROM {$this->_table}";
                }
            }

            // Gets UPDATE statements
            if($this->_instruction == 'UPDATE'){
                $query .= " {$this->_update} SET {$this->_set}";
            }

            // Gets WHERE statements
            if($this->_instruction == 'SELECT'|| $this->_instruction == 'SELECT DISTINCT' || $this->_instruction == 'UPDATE' || $this->_instruction == 'DELETE'){
                if(!empty($this->_where)){
                    $where = implode(' ', $this->_where);
                    $query .= " WHERE {$where}";
                }
            }

            // Gets INSERT statements
            if($this->_instruction == 'INSERT' || $this->_instruction == 'INSERT IGNORE'){
                if(!empty($this->_insert) && !empty($this->_values)){
                    $query .= " INTO {$this->_into} ({$this->_insert}) VALUES $this->_values";
                }
            }

            // Gets JOIN statements
            if(!empty($this->_join)){
                $join = implode(' ', $this->_join);
                $query .= " {$join}";
            }

            // Gets GROUP BY and ORDER BY statements
            if($this->_instruction == 'SELECT' || $this->_instruction == 'SELECT DISTINCT'){
                if(!empty($this->_group)){
                    $query .= " GROUP BY {$this->_group}";
                }

                if(!empty($this->_order)){
                    $order = implode(', ', $this->_order);
                    $query .= " ORDER BY {$order}";
                }
            }

            // Gets LIMIT statement
            if($this->_instruction == 'SELECT' || $this->_instruction == 'SELECT DISTINCT' || $this->_instruction == 'UPDATE' || $this->_instruction == 'DELETE'){
                if (!empty($this->_limit)) {
                    $query .= " LIMIT {$this->_limit}";
                }
            }

            // Returns the result
            return $query;
        }

        /**
         * Backup the current query parameters to an array.
         * @return array Array with the current query parameters.
         */
        private function backupQuery(){
            $params = get_object_vars($this);
            unset($params['_database']);
            unset($params['_table']);
            unset($params['_connection']);
            unset($params['_transactions']);
            return $params;
        }

        /**
         * Restores previously saved query parameters.
         * @param array $params Query parameters to restore.
         */
        private function restoreQuery(array $params){
            foreach($params as $key => $value) $this->$key = $value;
        }

        /**
         * Run the current built query.
         * @param bool $return (Optional) If the query should return a result.
         * @param bool $returnFirst (Optional) If the query should return a single result.
         * @return mixed If the query is successful and should return any results, will return an object with the first result or an array of\
         * results. Otherwise returns true on success or false on errors.
         */
        private function execute(bool $return = false, bool $returnFirst = false){
            // Initializes the transaction (if enabled)
            if($this->_transactions) $this->_connection->begin_transaction();
            
            try {
                // Run query and clear its data
                $query = $this->_connection->query($this->getQuery());
                $this->clearQuery();

                // Checks for query result
                if ($query !== false) {
                    $result = true;

                    // Checks for return type
                    if($return){
                        if($returnFirst){
                            // Return first row
                            $result = null;
                            if ($query->num_rows > 0) {
                                $row = $query->fetch_assoc();
                                $query->close();
                                $result = new Element($row);
                            }
                        }else{
                            // Return all rows
                            $result = [];
                            if ($query->num_rows > 0) {
                                $rows = $query->fetch_all(MYSQLI_ASSOC);
                                $query->close();
                                foreach ($rows as $row) $result[] = new Element($row);
                            }
                        }
                    }

                    // Commits the transaction (if enabled) and return the result
                    if ($this->_transactions) $this->_connection->commit();
                    return $result;
                }else{
                    // Query failed
                    return false;
                }
            } catch (mysqli_sql_exception $exception) {
                if($this->_transactions) $this->_connection->rollback();
                throw $exception;
                return false;
            }
        }
        
    }

?>