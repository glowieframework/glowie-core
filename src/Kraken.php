<?php
    namespace Glowie\Core;
    
    use mysqli;
    use Closure;
    use mysqli_sql_exception;

    /**
     * Model core for Glowie application.
     * @category Model
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 0.3-alpha
     */
    class Kraken{
        /**
         * Current database connection.
         * @var mysqli
         */
        protected $_connection;

        /**
         * Current table.
         * @var string
         */
        public $_table;

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
         * GROUP BY statements.
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
         * @var string[]
         */
        private $_insert;

        /**
         * INTO statement.
         * @var string
         */
        private $_into;

        /**
         * VALUES statement.
         * @var array
         */
        private $_values;

        /**
         * Creates a new database connection.
         * @param string $table (Optional) Table name to set as default.
         * @param string[] $database (Optional) Associative array with the connection settings.\
         * Use an empty array to connect to the globally defined database (in **app/Config.php**).
         */
        public function __construct(string $table = 'app', array $database = []){
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $this->setDatabase($database);
            $this->setTable($table);
        }

        /**
         * Sets the database connection.
         * @param string[] $database Associative array with the connection settings.\
         * Use an empty array to connect to the globally defined database (in **app/Config.php**).
         */
        public function setDatabase(array $database){
            if (empty($database)) $database = $GLOBALS['glowieConfig']['database'];
            if (!is_array($database)) trigger_error('setDatabase: Database connection settings must be an array');
            if (empty($database['host'])) trigger_error('setDatabase:  Database host not defined');
            if (empty($database['username'])) trigger_error('setDatabase:  Database username not defined');
            if (empty($database['password'])) $database['password'] = '';
            if (empty($database['port'])) $database['port'] = 3306;
            if (empty($database['db'])) trigger_error('setDatabase: Database name not defined');
            $this->_connection = new mysqli($database['host'], $database['username'], $database['password'], $database['db'], $database['port']);
        }
        
        /**
         * Sets the default table.
         * @param string $table Table name to set as default.
         */
        public function setTable(string $table){
            if (empty($table) || trim($table) == '') trigger_error('setTable: Table name should not be empty');
            $this->_table = $table;
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
         * @param string|string[] $table Table name or an array of tables.
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
         * Perfoms a table JOIN in the query.
         */
        public function join(string $table, $param1, $param2, $param3 = null, $type = 'INNER'){
            // Checks if the operator was passed
            if($param3 === null){
                $param3 = $param2;
                $param2 = '=';
            }
            $type = strtoupper($type);
            $this->_join[] = "{$type} JOIN {$table} ON {$param1} {$param2} {$param3}";
            return $this;
        }

        public function innerJoin(string $table, $param1, $param2, $param3 = null){
            return $this->join($table, $param1, $param2, $param3);
        }

        public function leftJoin(string $table, $param1, $param2, $param3 = null){
            return $this->join($table, $param1, $param2, $param3, 'LEFT');
        }

        public function rightJoin(string $table, $param1, $param2, $param3 = null){
            return $this->join($table, $param1, $param2, $param3, 'RIGHT');
        }

        public function fullJoin(string $table, $param1, $param2, $param3 = null){
            return $this->join($table, $param1, $param2, $param3, 'FULL OUTER');
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
            if(is_object($column) && ($column instanceof Closure)){
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
                foreach($param2 as $value) $values[] = $this->escape($value);
                $query .= "{$column} {$param1} \"{$values[0]}\" AND \"{$values[1]}\"";
            }else if(is_array($param2)){
                $values = [];
                foreach($param2 as $value) $values[] = "\"{$this->escape($value)}\"";
                if($param1 == '=') $param1 = 'IN';
                $values = implode(', ', $values);
                $query .= "{$column} {$param1} ($values)";
            }else if($param2 === 'NULL'){
                if($param1 == '=') $param1 = 'IS';
                $query .= "{$column} {$param1} NULL";
            }else{
                $query .= "{$column} {$param1} \"{$this->escape($param2)}\"";
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
            $query = !empty($this->_where) ? "{$type} " : "";
            $query .= $condition;
            $this->_where[] = $query;
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
         * @return Objectify Returns the first resulting row on success or false on errors.
         */
        public function fetchRow(){
            return $this->execute(true, true);
        }
        
        /**
         * Fetches all results from a SELECT query.
         * @return Objectify[] Returns an array with all resulting rows on success or false on errors.
         */
        public function fetchAll(){
            return $this->execute(true);
        }

        /**
         * Inserts data into the table.
         * @param mixed $param1 If `$param2` isset, the table to insert data. Otherwise, an associative array\
         * relating fields and values to insert.
         * @param mixed $param2 (Optional) Array with data to insert if `$param1` is the table.
         * @param bool $ignore (Optional) Perform an INSERT IGNORE query.
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
                    foreach($row as $value){
                        $value = $this->escape($value);
                        $result[] = "\"{$value}\"";
                    }
                    $result = implode(', ', $result);
                    $values[] = "({$result})";
                }
                $this->_values = $values;
            }else{
               foreach($param2 as $field => $value){
                  $fields[] = $field;
                  $value = $this->escape($value);
                  $values[] = "\"{$value}\"";
               }
               $values = implode(', ', $values);
               $values = ["({$values})"];
            }
            
            // Stores data to the query builder and run
            $this->_values = $values;
            $this->_insert = $fields;
            return $this->execute();
        }

        public function insertIgnore($param1, $param2 = null){
            return $this->insert($param1, $param2, true);
        }

        /**
         * Returns the current built query.
         * @return string Current built query.
         */
        public function getQuery(){
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
                    $fields = implode(', ', $this->_insert);
                    $values = implode(', ', $this->_values);
                    $query .= " INTO {$this->_into} ({$fields}) VALUES $values";
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
         * Clears the current built query.
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
            $this->_insert = [];
            $this->_into = '';
            $this->_values = [];
        }

        /**
         * Run the current built query.
         * @param bool $return (Optional) If the query should return a result.
         * @param bool $returnFirst (Optional) If the query should return a single result.
         * @return mixed If the query is successful and should return any results, will return an object with the first result or an array of\
         * results. Otherwise returns true on success or false on errors.
         */
        private function execute($return = false, $returnFirst = false){
            $this->_connection->begin_transaction();
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
                            $result = new Objectify();
                            if ($query->num_rows > 0) {
                                $row = $query->fetch_assoc();
                                $query->close();
                                $result = new Objectify($row);
                            }
                        }else{
                            // Return all rows
                            $result = [];
                            if ($query->num_rows > 0) {
                                $rows = $query->fetch_all(MYSQLI_ASSOC);
                                $query->close();
                                foreach ($rows as $row) $result[] = new Objectify($row);
                            }
                        }
                    }

                    // Commits the transaction and return the result
                    $this->_connection->commit();
                    return $result;
                }else{
                    // Query failed
                    return false;
                }
            } catch (mysqli_sql_exception $exception) {
                $this->_connection->rollback();
                throw $exception;
            }
        }
        
    }

?>