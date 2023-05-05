<?php
    namespace Glowie\Core\Database;

    use Glowie\Core\Element;
    use Glowie\Core\Traits\DatabaseTrait;
    use Glowie\Core\Exception\QueryException;
    use Closure;
    use stdClass;
    use Exception;
    use Util;

    /**
     * Database ORM toolkit for Glowie application.
     * @category Database
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) Glowie
     * @license MIT
     * @link https://eugabrielsilva.tk/glowie
     */
    class Kraken{
        use DatabaseTrait;

        /**
         * Safe UPDATE/DELETE queries.
         * @var bool
         */
        private $_safe = true;

        /**
         * SELECT statement.
         * @var string
         */
        protected $_select;

        /**
         * FROM statement.
         * @var string
         */
        private $_from;

        /**
         * JOIN statements.
         * @var array
         */
        private $_join;

        /**
         * WHERE statements.
         * @var array
         */
        private $_where;

        /**
         * GROUP BY statements.
         * @var array
         */
        private $_group;

        /**
         * HAVING statements.
         * @var array
         */
        private $_having;

        /**
         * ORDER BY statements.
         * @var array
         */
        private $_order;

        /**
         * LIMIT statements.
         * @var array
         */
        private $_limit;

        /**
         * DELETE table names.
         * @var string
         */
        private $_delete;

        /**
         * INSERT fields.
         * @var string
         */
        private $_insert;

        /**
         * VALUES statement.
         * @var string
         */
        private $_values;

        /**
         * ON DUPLICATE KEY statement.
         * @var string
         */
        private $_duplicate;

        /**
         * SET statement.
         * @var string
         */
        private $_set;

        /**
         * UNION statement.
         * @var string
         */
        private $_union;

        /**
         * Last insert ID.
         * @var int
         */
        private $_lastInsertId = 0;

        /**
         * Creates a new Kraken database instance.
         * @param string $table (Optional) Table name to set as default.
         * @param string $database (Optional) Database connection name (from your app configuration).
         */
        public function __construct(string $table = 'glowie', string $database = 'default'){
            $this->table($table);
            $this->database($database);
        }

        /**
         * Disables WHERE checking in UPDATE or DELETE queries.
         * @return $this Current instance for nested calls.
         */
        public function noSafeUpdateDeletes(){
            $this->_safe = false;
            return $this;
        }

        /**
         * Enables WHERE checking in UPDATE or DELETE queries.
         * @return $this Current instance for nested calls.
         */
        public function withSafeUpdateDeletes(){
            $this->_safe = true;
            return $this;
        }

        /**
         * Prepares a SELECT query.
         * @param string|array $columns (Optional) Columns to select in the query. Can be a single column name or an array of columns.\
         * You can also use a raw SELECT query.
         * @return $this Current instance for nested calls.
         */
        public function select($columns = '*'){
            $this->_instruction = 'SELECT';
            $this->_select = implode(', ', (array)$columns);
            return $this;
        }

        /**
         * Appends a SELECT statement to the existing one in the query.
         * @param string|array $columns (Optional) Columns to select in the query. Can be a single column name or an array of columns.\
         * You can also use a raw SELECT query.
         * @return $this Current instance for nested calls.
         */
        public function addSelect($columns = '*'){
            $this->_instruction = 'SELECT';
            $value = implode(', ', (array)$columns);
            $this->_select .= (!Util::isEmpty($this->_select) ? ', ' : '') . $value;
            return $this;
        }

        /**
         * Sets the current SELECT query as DISTINCT.
         * @return $this Current instance for nested calls.
         */
        public function distinct(){
            $this->_instruction = 'SELECT DISTINCT';
            return $this;
        }

        /**
         * Sets the query FROM statement.
         * @param string $table Table name or a raw FROM query.
         * @return $this Current instance for nested calls.
         */
        public function from(string $table){
            $this->_from = $table;
            return $this;
        }

        /**
         * Adds a table JOIN in the query.
         * @param string $table Table name to JOIN.
         * @param string|Closure $param1 First condition parameter or a grouped ON closure.
         * @param string|null $param2 If `$param3` isset, the operator used in the condition. Otherwise, the second condition parameter.
         * @param string|null $param3 (Optional) Second condition parameter if `$param2` is the operator.
         * @param string $type (Optional) JOIN type (INNER, LEFT, RIGHT or FULL).
         * @return $this Current instance for nested calls.
         */
        public function join(string $table, $param1, ?string $param2 = null, ?string $param3 = null, string $type = 'INNER'){
            // Adds the join
            $this->_join[] = "{$type} JOIN {$table}";

            // Checks for grouped ON closure
            if($param1 instanceof Closure){
                if(!empty($this->_join) && end($this->_join) != '('){
                    $this->_join[] = "ON";
                    $this->_join[] = "(";
                }else{
                    $this->_join[] = "(";
                }

                call_user_func_array($param1, [$this]);
                $this->_join[] = ')';
                return $this;
            }

            // Checks if the operator was passed
            if(is_null($param3)){
                $param3 = $param2;
                $param2 = '=';
            }

            $this->_join[] = "ON {$param1} {$param2} {$param3}";
            return $this;
        }

        /**
         * Adds a raw table JOIN in the query.
         * @param string $join Full JOIN clause.
         * @return $this Current instance for nested calls.
         */
        public function rawJoin(string $join){
            $this->_join[] = $join;
            return $this;
        }

        /**
         * Adds a table INNER JOIN in the query.
         * @param string $table Table name to JOIN.
         * @param string|Closure $param1 First condition parameter or a grouped ON closure.
         * @param string|null $param2 If `$param3` isset, the operator used in the condition. Otherwise, the second condition parameter.
         * @param string|null $param3 (Optional) Second condition parameter if `$param2` is the operator.
         * @return $this Current instance for nested calls.
         */
        public function innerJoin(string $table, $param1, ?string $param2 = null, ?string $param3 = null){
            return $this->join($table, $param1, $param2, $param3);
        }

        /**
         * Adds a table LEFT JOIN in the query.
         * @param string $table Table name to JOIN.
         * @param string|Closure $param1 First condition parameter or a grouped ON closure.
         * @param string|null $param2 If `$param3` isset, the operator used in the condition. Otherwise, the second condition parameter.
         * @param string|null $param3 (Optional) Second condition parameter if `$param2` is the operator.
         * @return $this Current instance for nested calls.
         */
        public function leftJoin(string $table, $param1, ?string $param2 = null, ?string $param3 = null){
            return $this->join($table, $param1, $param2, $param3, 'LEFT');
        }

        /**
         * Adds a table RIGHT JOIN in the query.
         * @param string $table Table name to JOIN.
         * @param string|Closure $param1 First condition parameter or a grouped ON closure.
         * @param string|null $param2 If `$param3` isset, the operator used in the condition. Otherwise, the second condition parameter.
         * @param string|null $param3 (Optional) Second condition parameter if `$param2` is the operator.
         * @return $this Current instance for nested calls.
         */
        public function rightJoin(string $table, $param1, ?string $param2 = null, ?string $param3 = null){
            return $this->join($table, $param1, $param2, $param3, 'RIGHT');
        }

        /**
         * Adds a table FULL JOIN in the query.
         * @param string $table Table name to JOIN.
         * @param string|Closure $param1 First condition parameter or a grouped ON closure.
         * @param string|null $param2 If `$param3` isset, the operator used in the condition. Otherwise, the second condition parameter.
         * @param string|null $param3 (Optional) Second condition parameter if `$param2` is the operator.
         * @return $this Current instance for nested calls.
         */
        public function fullJoin(string $table, $param1, ?string $param2 = null, ?string $param3 = null){
            return $this->join($table, $param1, $param2, $param3, 'FULL');
        }

        /**
         * Adds an ON condition to the last JOIN statement in the query.
         * @param string $param1 First condition parameter.
         * @param string $param2 If `$param3` isset, the operator used in the condition. Otherwise, the second condition parameter.
         * @param string|null $param3 (Optional) Second condition parameter if `$param2` is the operator.
         * @param string $type (Optional) Chaining type (AND or OR).
         * @return $this Current instance for nested calls.
         */
        public function on(string $param1, string $param2, ?string $param3 = null, string $type = 'AND'){
            // Checks for empty joins
            if(empty($this->_join)) throw new Exception('on(): There are no JOIN statements in the query yet');

            // Checks for the condition type
            if(end($this->_join) == '(') $type = "";

            // Checks if the operator was passed
            if(is_null($param3)){
                $param3 = $param2;
                $param2 = '=';
            }

            $this->_join[] = "{$type} {$param1} {$param2} {$param3}";
            return $this;
        }

        /**
         * Adds an OR ON condition to the last JOIN statement in the query.
         * @param string $param1 First condition parameter.
         * @param string $param2 If `$param3` isset, the operator used in the condition. Otherwise, the second condition parameter.
         * @param string|null $param3 (Optional) Second condition parameter if `$param2` is the operator.
         * @return $this Current instance for nested calls.
         */
        public function orOn(string $param1, string $param2, ?string $param3 = null){
            return $this->on($param1, $param2, $param3, 'OR');
        }

        /**
         * Adds a WHERE condition to the query.
         * @param string|array|Closure $param1 Column name, array of WHERE conditions or a grouped WHERE closure.
         * @param mixed $param2 (Optional) If `$param3` isset, the operator used in the condition. Otherwise, the value to check to.
         * @param mixed $param3 (Optional) Value if `$param2` is the operator.
         * @param string $type (Optional) Chaining type (AND or OR).
         * @return $this Current instance for nested calls.
         */
        public function where($param1, $param2 = null, $param3 = null, string $type = 'AND'){
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
            if($param1 instanceof Closure){
                if(!empty($this->_where) && end($this->_where) != '('){
                    $this->_where[] = "{$type} ";
                    $this->_where[] = "(";
                }else{
                    $this->_where[] = "(";
                }

                call_user_func_array($param1, [$this]);
                $this->_where[] = ')';
                return $this;
            }else if(is_array($param1)){
                foreach($param1 as $condition){
                    if(!is_array($condition) || count($condition) < 2) throw new Exception('where(): Multiple WHERE conditions must be an array with at least two parameters');
                    $this->where($condition[0], $condition[1], $condition[2] ?? null);
                }
                return $this;
            }

            // Checks if the operator was passed
            if(is_null($param3)){
                $param3 = $param2;
                $param2 = '=';
            }

            // Checks operation types
            $param2 = strtoupper($param2);
            if(($param2 == 'BETWEEN' || $param2 == 'NOT BETWEEN') && is_array($param3)){
                $values = [];

                // Escaping values
                foreach($param3 as $value){
                    if($value instanceof stdClass){
                        $values[] = $value->value;
                    }else if($value === 'NULL' || is_null($value)){
                        $values[] = 'NULL';
                    }else{
                        $values[] = "\"{$this->escape($value)}\"";
                    }
                }

                $query .= "{$param1} {$param2} {$values[0]} AND {$values[1]}";
            }else if(is_array($param3)){
                $values = [];

                // Escaping values
                foreach($param3 as $value){
                    if($value instanceof stdClass){
                        $values[] = $value->value;
                    }else if($value === 'NULL' || is_null($value)){
                        $values[] = 'NULL';
                    }else{
                        $values[] = "\"{$this->escape($value)}\"";
                    }
                }

                if($param2 == '=') $param2 = 'IN';
                $values = implode(', ', $values);
                $query .= "{$param1} {$param2} ($values)";
            }else if($param3 === 'NULL' || is_null($param3)){
                if($param2 == '=') $param2 = 'IS';
                $query .= "{$param1} {$param2} NULL";
            }else{
                // Escaping values
                if($param3 instanceof stdClass){
                    $param3 = $param3->value;
                }else{
                    $param3 = "\"{$this->escape($param3)}\"";
                }

                $query .= "{$param1} {$param2} {$param3}";
            }

            $this->_where[] = $query;
            return $this;
        }

        /**
         * Adds an OR WHERE condition to the query.
         * @param string|array|Closure $param1 Column name, array of WHERE conditions or a grouped WHERE closure.
         * @param mixed $param2 (Optional) If `$param3` isset, the operator used in the condition. Otherwise, the value to check to.
         * @param mixed $param3 (Optional) Value if `$param2` is the operator.
         * @return $this Current instance for nested calls.
         */
        public function orWhere($param1, $param2 = null, $param3 = null){
            return $this->where($param1, $param2, $param3, 'OR');
        }

        /**
         * Adds a raw WHERE condition to the query.\
         * **Note: This does not prevent SQL injection attacks.**
         * @param string $condition Full WHERE condition.
         * @param string $type (Optional) Chaining type (AND or OR).
         * @return $this Current instance for nested calls.
         */
        public function rawWhere(string $condition, string $type = 'AND'){
            $this->_where[] = (!empty($this->_where) ? "{$type} " : "") . $condition;
            return $this;
        }

        /**
         * Adds a raw OR WHERE condition to the query.\
         * **Note: This does not prevent SQL injection attacks.**
         * @param string $condition Full WHERE condition.
         * @return $this Current instance for nested calls.
         */
        public function orRawWhere(string $condition){
            return $this->rawWhere($condition, 'OR');
        }

        /**
         * Adds a WHERE IN condition to the query.
         * @param string $column Column name.
         * @param array $values Array of values to check to.
         * @return $this Current instance for nested calls.
         */
        public function whereIn(string $column, array $values){
            return $this->where($column, $values);
        }

        /**
         * Adds an OR WHERE IN condition to the query.
         * @param string $column Column name.
         * @param array $values Array of values to check to.
         * @return $this Current instance for nested calls.
         */
        public function orWhereIn(string $column, array $values){
            return $this->where($column, 'IN', $values, 'OR');
        }

        /**
         * Adds a WHERE NOT IN condition to the query.
         * @param string $column Column name.
         * @param array $values Array of values to check to.
         * @return $this Current instance for nested calls.
         */
        public function whereNotIn(string $column, array $values){
            return $this->where($column, 'NOT IN', $values);
        }

        /**
         * Adds an OR WHERE NOT IN condition to the query.
         * @param string $column Column name.
         * @param array $values Array of values to check to.
         * @return $this Current instance for nested calls.
         */
        public function orWhereNotIn(string $column, array $values){
            return $this->where($column, 'NOT IN', $values, 'OR');
        }

        /**
         * Adds a WHERE BETWEEN condition to the query.
         * @param string $column Column name.
         * @param mixed $value1 First value in the range.
         * @param mixed $value2 Last value in the range.
         * @return $this Current instance for nested calls.
         */
        public function whereBetween(string $column, $value1, $value2){
            return $this->where($column, 'BETWEEN', [$value1, $value2]);
        }

        /**
         * Adds an OR WHERE BETWEEN condition to the query.
         * @param string $column Column name.
         * @param mixed $value1 First value in the range.
         * @param mixed $value2 Last value in the range.
         * @return $this Current instance for nested calls.
         */
        public function orWhereBetween(string $column, $value1, $value2){
            return $this->where($column, 'BETWEEN', [$value1, $value2], 'OR');
        }

        /**
         * Adds a WHERE NOT BETWEEN condition to the query.
         * @param string $column Column name.
         * @param mixed $value1 First value in the range.
         * @param mixed $value2 Last value in the range.
         * @return $this Current instance for nested calls.
         */
        public function whereNotBetween(string $column, $value1, $value2){
            return $this->where($column, 'NOT BETWEEN', [$value1, $value2]);
        }

        /**
         * Adds an OR WHERE NOT BETWEEN condition to the query.
         * @param string $column Column name.
         * @param mixed $value1 First value in the range.
         * @param mixed $value2 Last value in the range.
         * @return $this Current instance for nested calls.
         */
        public function orWhereNotBetween(string $column, $value1, $value2){
            return $this->where($column, 'NOT BETWEEN', [$value1, $value2], 'OR');
        }

        /**
         * Adds a WHERE NULL condition to the query.
         * @param string $column Column name.
         * @return $this Current instance for nested calls.
         */
        public function whereNull(string $column){
            return $this->where($column, 'NULL');
        }

        /**
         * Adds an OR WHERE NULL condition to the query.
         * @param string $column Column name.
         * @return $this Current instance for nested calls.
         */
        public function orWhereNull(string $column){
            return $this->where($column, 'IS', 'NULL', 'OR');
        }

        /**
         * Adds a WHERE NOT NULL condition to the query.
         * @param string $column Column name.
         * @return $this Current instance for nested calls.
         */
        public function whereNotNull(string $column){
            return $this->where($column, 'IS NOT', 'NULL');
        }

        /**
         * Adds an OR WHERE NOT NULL condition to the query.
         * @param string $column Column name.
         * @return $this Current instance for nested calls.
         */
        public function orWhereNotNull(string $column){
            return $this->where($column, 'IS NOT', 'NULL', 'OR');
        }

        /**
         * Adds a WHERE condition to the query comparing a date value in a date column.
         * @param string $column Column name **(must be a DATE or DATETIME column)**.
         * @param mixed $param1 If `$param2` isset, the operator used in the condition. Otherwise, the date value to check to.
         * @param mixed $param2 (Optional) Value if `$param1` is the operator.
         * @return $this Current instance for nested calls.
         */
        public function whereDate(string $column, $param1, $param2 = null){
            return $this->where("DATE({$column})", $param1, $param2);
        }

        /**
         * Adds an OR WHERE condition to the query comparing a date value in a date column.
         * @param string $column Column name **(must be a DATE or DATETIME column)**.
         * @param mixed $param1 If `$param2` isset, the operator used in the condition. Otherwise, the date value to check to.
         * @param mixed $param2 (Optional) Value if `$param1` is the operator.
         * @return $this Current instance for nested calls.
         */
        public function orWhereDate(string $column, $param1, $param2 = null){
            return $this->where("DATE({$column})", $param1, $param2, 'OR');
        }

        /**
         * Adds a WHERE condition to the query comparing a day value in a date column.
         * @param string $column Column name **(must be a DATE or DATETIME column)**.
         * @param mixed $param1 If `$param2` isset, the operator used in the condition. Otherwise, the day value to check to.
         * @param mixed $param2 (Optional) Value if `$param1` is the operator.
         * @return $this Current instance for nested calls.
         */
        public function whereDay(string $column, $param1, $param2 = null){
            return $this->where("DAY({$column})", $param1, $param2);
        }

        /**
         * Adds an OR WHERE condition to the query comparing a day value in a date column.
         * @param string $column Column name **(must be a DATE or DATETIME column)**.
         * @param mixed $param1 If `$param2` isset, the operator used in the condition. Otherwise, the day value to check to.
         * @param mixed $param2 (Optional) Value if `$param1` is the operator.
         * @return $this Current instance for nested calls.
         */
        public function orWhereDay(string $column, $param1, $param2 = null){
            return $this->where("DAY({$column})", $param1, $param2, 'OR');
        }

        /**
         * Adds a WHERE condition to the query comparing a month value in a date column.
         * @param string $column Column name **(must be a DATE or DATETIME column)**.
         * @param mixed $param1 If `$param2` isset, the operator used in the condition. Otherwise, the month value to check to.
         * @param mixed $param2 (Optional) Value if `$param1` is the operator.
         * @return $this Current instance for nested calls.
         */
        public function whereMonth(string $column, $param1, $param2 = null){
            return $this->where("MONTH({$column})", $param1, $param2);
        }

        /**
         * Adds an OR WHERE condition to the query comparing a month value in a date column.
         * @param string $column Column name **(must be a DATE or DATETIME column)**.
         * @param mixed $param1 If `$param2` isset, the operator used in the condition. Otherwise, the month value to check to.
         * @param mixed $param2 (Optional) Value if `$param1` is the operator.
         * @return $this Current instance for nested calls.
         */
        public function orWhereMonth(string $column, $param1, $param2 = null){
            return $this->where("MONTH({$column})", $param1, $param2, 'OR');
        }

        /**
         * Adds a WHERE condition to the query comparing an year value in a date column.
         * @param string $column Column name **(must be a DATE, DATETIME or YEAR column)**.
         * @param mixed $param1 If `$param2` isset, the operator used in the condition. Otherwise, the year value to check to.
         * @param mixed $param2 (Optional) Value if `$param1` is the operator.
         * @return $this Current instance for nested calls.
         */
        public function whereYear(string $column, $param1, $param2 = null){
            return $this->where("YEAR({$column})", $param1, $param2);
        }

        /**
         * Adds an OR WHERE condition to the query comparing an year value in a date column.
         * @param string $column Column name **(must be a DATE, DATETIME or YEAR column)**.
         * @param mixed $param1 If `$param2` isset, the operator used in the condition. Otherwise, the year value to check to.
         * @param mixed $param2 (Optional) Value if `$param1` is the operator.
         * @return $this Current instance for nested calls.
         */
        public function orWhereYear(string $column, $param1, $param2 = null){
            return $this->where("YEAR({$column})", $param1, $param2, 'OR');
        }

        /**
         * Adds a WHERE condition to the query comparing a time value in a time column.
         * @param string $column Column name **(must be a TIME or DATETIME column)**.
         * @param mixed $param1 If `$param2` isset, the operator used in the condition. Otherwise, the time value to check to.
         * @param mixed $param2 (Optional) Value if `$param1` is the operator.
         * @return $this Current instance for nested calls.
         */
        public function whereTime(string $column, $param1, $param2 = null){
            return $this->where("TIME({$column})", $param1, $param2);
        }

        /**
         * Adds an OR WHERE condition to the query comparing a time value in a time column.
         * @param string $column Column name **(must be a TIME or DATETIME column)**.
         * @param mixed $param1 If `$param2` isset, the operator used in the condition. Otherwise, the time value to check to.
         * @param mixed $param2 (Optional) Value if `$param1` is the operator.
         * @return $this Current instance for nested calls.
         */
        public function orWhereTime(string $column, $param1, $param2 = null){
            return $this->where("TIME({$column})", $param1, $param2, 'OR');
        }

        /**
         * Adds a WHERE condition to the query comparing the hours value in a time column.
         * @param string $column Column name **(must be a TIME or DATETIME column)**.
         * @param mixed $param1 If `$param2` isset, the operator used in the condition. Otherwise, the hours value to check to.
         * @param mixed $param2 (Optional) Value if `$param1` is the operator.
         * @return $this Current instance for nested calls.
         */
        public function whereHour(string $column, $param1, $param2 = null){
            return $this->where("HOUR({$column})", $param1, $param2);
        }

        /**
         * Adds an OR WHERE condition to the query comparing the hours value in a time column.
         * @param string $column Column name **(must be a TIME or DATETIME column)**.
         * @param mixed $param1 If `$param2` isset, the operator used in the condition. Otherwise, the hours value to check to.
         * @param mixed $param2 (Optional) Value if `$param1` is the operator.
         * @return $this Current instance for nested calls.
         */
        public function orWhereHour(string $column, $param1, $param2 = null){
            return $this->where("HOUR({$column})", $param1, $param2, 'OR');
        }

        /**
         * Adds a WHERE condition to the query comparing the minutes value in a time column.
         * @param string $column Column name **(must be a TIME or DATETIME column)**.
         * @param mixed $param1 If `$param2` isset, the operator used in the condition. Otherwise, the minutes value to check to.
         * @param mixed $param2 (Optional) Value if `$param1` is the operator.
         * @return $this Current instance for nested calls.
         */
        public function whereMinute(string $column, $param1, $param2 = null){
            return $this->where("MINUTE({$column})", $param1, $param2);
        }

        /**
         * Adds an OR WHERE condition to the query comparing the minutes value in a time column.
         * @param string $column Column name **(must be a TIME or DATETIME column)**.
         * @param mixed $param1 If `$param2` isset, the operator used in the condition. Otherwise, the minutes value to check to.
         * @param mixed $param2 (Optional) Value if `$param1` is the operator.
         * @return $this Current instance for nested calls.
         */
        public function orWhereMinute(string $column, $param1, $param2 = null){
            return $this->where("MINUTE({$column})", $param1, $param2, 'OR');
        }

        /**
         * Adds a WHERE condition to the query comparing the seconds value in a time column.
         * @param string $column Column name **(must be a TIME or DATETIME column)**.
         * @param mixed $param1 If `$param2` isset, the operator used in the condition. Otherwise, the seconds value to check to.
         * @param mixed $param2 (Optional) Value if `$param1` is the operator.
         * @return $this Current instance for nested calls.
         */
        public function whereSecond(string $column, $param1, $param2 = null){
            return $this->where("SECOND({$column})", $param1, $param2);
        }

        /**
         * Adds an OR WHERE condition to the query comparing the seconds value in a time column.
         * @param string $column Column name **(must be a TIME or DATETIME column)**.
         * @param mixed $param1 If `$param2` isset, the operator used in the condition. Otherwise, the seconds value to check to.
         * @param mixed $param2 (Optional) Value if `$param1` is the operator.
         * @return $this Current instance for nested calls.
         */
        public function orWhereSecond(string $column, $param1, $param2 = null){
            return $this->where("SECOND({$column})", $param1, $param2, 'OR');
        }

        /**
         * Adds a WHERE condition to the query comparing the value of two columns.
         * @param string $column First column name to compare.
         * @param string $param1 If `$param2` isset, the operator used in the condition. Otherwise, the second column name to compare.
         * @param string|null $param2 (Optional) Second column name if `$param1` is the operator.
         */
        public function whereColumn(string $column, string $param1, $param2 = null){
            if(is_null($param2)){
                $param2 = $param1;
                $param1 = '=';
            }
            return $this->where($column, $param1, self::raw($param2));
        }

        /**
         * Adds an OR WHERE condition to the query comparing the value of two columns.
         * @param string $column First column name to compare.
         * @param string $param1 If `$param2` isset, the operator used in the condition. Otherwise, the second column name to compare.
         * @param string|null $param2 (Optional) Second column name if `$param1` is the operator.
         */
        public function orWhereColumn(string $column, string $param1, $param2 = null){
            if(is_null($param2)){
                $param2 = $param1;
                $param1 = '=';
            }
            return $this->where($column, $param1, self::raw($param2), 'OR');
        }

        /**
         * Adds a GROUP BY statement to the query.
         * @param string|array $column Column name to group. Can be a single column name or an array of columns.\
         * You can also use a raw GROUP BY statement.
         * @return $this Current instance for nested calls.
         */
        public function groupBy($column){
            $this->_group[] = implode(', ', (array)$column);
            return $this;
        }

        /**
         * Adds a HAVING condition to the query.
         * @param string|array|Closure $param1 Column name, array of HAVING conditions or a grouped HAVING closure.
         * @param mixed $param2 (Optional) If `$param3` isset, the operator used in the condition. Otherwise, the value to check to.
         * @param mixed $param3 (Optional) Value if `$param2` is the operator.
         * @param string $type (Optional) Chaining type (AND or OR).
         * @return $this Current instance for nested calls.
         */
        public function having($param1, $param2 = null, $param3 = null, string $type = 'AND'){
            // Checks for the condition type
            $type = strtoupper($type);
            if(!empty($this->_having)){
                if(end($this->_having) == '('){
                    $query = "";
                }else{
                    $query = "{$type} ";
                }
            }else{
                $query = "";
            }

            // Checks for grouped havings
            if($param1 instanceof Closure){
                if(!empty($this->_having) && end($this->_having) != '('){
                    $this->_having[] = "{$type} ";
                    $this->_having[] = "(";
                }else{
                    $this->_having[] = "(";
                }

                call_user_func_array($param1, [$this]);
                $this->_having[] = ')';
                return $this;
            }else if(is_array($param1)){
                foreach($param1 as $condition){
                    if(!is_array($condition) || count($condition) < 2) throw new Exception('having(): Multiple HAVING conditions must be an array with at least two parameters');
                    $this->having($condition[0], $condition[1], $condition[2] ?? null);
                }
                return $this;
            }

            // Checks if the operator was passed
            if(is_null($param3)){
                $param3 = $param2;
                $param2 = '=';
            }

            // Checks operation types
            $param2 = strtoupper($param2);
            if(($param2 == 'BETWEEN' || $param2 == 'NOT BETWEEN') && is_array($param3)){
                $values = [];

                // Escaping values
                foreach($param3 as $value){
                    if($value instanceof stdClass){
                        $values[] = $value->value;
                    }else if($value === 'NULL' || is_null($value)){
                        $values[] = 'NULL';
                    }else{
                        $values[] = "\"{$this->escape($value)}\"";
                    }
                }

                $query .= "{$param1} {$param2} {$values[0]} AND {$values[1]}";
            }else if(is_array($param3)){
                $values = [];

                // Escaping values
                foreach($param3 as $value){
                    if($value instanceof stdClass){
                        $values[] = $value->value;
                    }else if($value === 'NULL' || is_null($value)){
                        $values[] = 'NULL';
                    }else{
                        $values[] = "\"{$this->escape($value)}\"";
                    }
                }

                if($param2 == '=') $param2 = 'IN';
                $values = implode(', ', $values);
                $query .= "{$param1} {$param2} ($values)";
            }else if($param3 === 'NULL' || is_null($param3)){
                if($param2 == '=') $param2 = 'IS';
                $query .= "{$param1} {$param2} NULL";
            }else{
                // Escaping values
                if($param3 instanceof stdClass){
                    $param3 = $param3->value;
                }else{
                    $param3 = "\"{$this->escape($param3)}\"";
                }

                $query .= "{$param1} {$param2} {$param3}";
            }

            $this->_having[] = $query;
            return $this;
        }

        /**
         * Adds an OR HAVING condition to the query.
         * @param string|array|Closure $param1 Column name, array of HAVING conditions or a grouped HAVING closure.
         * @param mixed $param2 (Optional) If `$param3` isset, the operator used in the condition. Otherwise, the value to check to.
         * @param mixed $param3 (Optional) Value if `$param2` is the operator.
         * @return $this Current instance for nested calls.
         */
        public function orHaving($param1, $param2 = null, $param3 = null){
            return $this->having($param1, $param2, $param3, 'OR');
        }

        /**
         * Adds a raw HAVING condition to the query.\
         * **Note: This does not prevent SQL injection attacks.**
         * @param string $condition Full HAVING condition.
         * @param string $type (Optional) Chaining type (AND or OR).
         * @return $this Current instance for nested calls.
         */
        public function rawHaving(string $condition, string $type = 'AND'){
            $this->_having[] = (!empty($this->_having) ? "{$type} " : "") . $condition;
            return $this;
        }

        /**
         * Adds a raw OR HAVING condition to the query.\
         * **Note: This does not prevent SQL injection attacks.**
         * @param string $condition Full HAVING condition.
         * @return $this Current instance for nested calls.
         */
        public function orRawHaving(string $condition){
            return $this->rawHaving($condition, 'OR');
        }

        /**
         * Adds a HAVING IN condition to the query.
         * @param string $column Column name.
         * @param array $values Array of values to check to.
         * @return $this Current instance for nested calls.
         */
        public function havingIn(string $column, array $values){
            return $this->having($column, $values);
        }

        /**
         * Adds an OR HAVING IN condition to the query.
         * @param string $column Column name.
         * @param array $values Array of values to check to.
         * @return $this Current instance for nested calls.
         */
        public function orHavingIn(string $column, array $values){
            return $this->having($column, 'IN', $values, 'OR');
        }

        /**
         * Adds a HAVING NOT IN condition to the query.
         * @param string $column Column name.
         * @param array $values Array of values to check to.
         * @return $this Current instance for nested calls.
         */
        public function havingNotIn(string $column, array $values){
            return $this->having($column, 'NOT IN', $values);
        }

        /**
         * Adds an OR HAVING NOT IN condition to the query.
         * @param string $column Column name.
         * @param array $values Array of values to check to.
         * @return $this Current instance for nested calls.
         */
        public function orHavingNotIn(string $column, array $values){
            return $this->having($column, 'NOT IN', $values, 'OR');
        }

        /**
         * Adds a HAVING BETWEEN condition to the query.
         * @param string $column Column name.
         * @param mixed $value1 First value in the range.
         * @param mixed $value2 Last value in the range.
         * @return $this Current instance for nested calls.
         */
        public function havingBetween(string $column, $value1, $value2){
            return $this->having($column, 'BETWEEN', [$value1, $value2]);
        }

        /**
         * Adds an OR HAVING BETWEEN condition to the query.
         * @param string $column Column name.
         * @param mixed $value1 First value in the range.
         * @param mixed $value2 Last value in the range.
         * @return $this Current instance for nested calls.
         */
        public function orHavingBetween(string $column, $value1, $value2){
            return $this->having($column, 'BETWEEN', [$value1, $value2], 'OR');
        }

        /**
         * Adds a HAVING NOT BETWEEN condition to the query.
         * @param string $column Column name.
         * @param mixed $value1 First value in the range.
         * @param mixed $value2 Last value in the range.
         * @return $this Current instance for nested calls.
         */
        public function havingNotBetween(string $column, $value1, $value2){
            return $this->having($column, 'NOT BETWEEN', [$value1, $value2]);
        }

        /**
         * Adds an OR HAVING NOT BETWEEN condition to the query.
         * @param string $column Column name.
         * @param mixed $value1 First value in the range.
         * @param mixed $value2 Last value in the range.
         * @return $this Current instance for nested calls.
         */
        public function orHavingNotBetween(string $column, $value1, $value2){
            return $this->having($column, 'NOT BETWEEN', [$value1, $value2], 'OR');
        }

        /**
         * Adds a HAVING NULL condition to the query.
         * @param string $column Column name.
         * @return $this Current instance for nested calls.
         */
        public function havingNull(string $column){
            return $this->having($column, 'NULL');
        }

        /**
         * Adds an OR HAVING NULL condition to the query.
         * @param string $column Column name.
         * @return $this Current instance for nested calls.
         */
        public function orHavingNull(string $column){
            return $this->having($column, 'IS', 'NULL', 'OR');
        }

        /**
         * Adds a HAVING NOT NULL condition to the query.
         * @param string $column Column name.
         * @return $this Current instance for nested calls.
         */
        public function havingNotNull(string $column){
            return $this->having($column, 'IS NOT', 'NULL');
        }

        /**
         * Adds an OR HAVING NOT NULL condition to the query.
         * @param string $column Column name.
         * @return $this Current instance for nested calls.
         */
        public function orHavingNotNull(string $column){
            return $this->having($column, 'IS NOT', 'NULL', 'OR');
        }

        /**
         * Adds an ORDER BY statement to the query.
         * @param string $column Column name.
         * @param string $direction (Optional) Sorting direction **(ASC or DESC)**.
         * @return $this Current instance for nested calls.
         */
        public function orderBy(string $column, string $direction = 'ASC'){
            $direction = strtoupper($direction);
            $this->_order[] = "{$column} {$direction}";
            return $this;
        }

        /**
         * Adds a random ORDER BY statement to the query.
         * @return $this Current instance for nested calls.
         */
        public function orderByRandom(){
            $this->_order[] = "RAND()";
            return $this;
        }

        /**
         * Adds a raw ORDER BY statement to the query.
         * @param string $statement Full ORDER BY statement.
         * @return $this Current instance for nested calls.
         */
        public function rawOrderBy(string $statement){
            $this->_order[] = $statement;
            return $this;
        }

        /**
         * Sets the query LIMIT statement.
         * @param int $param1 If `$param2` isset, the offset setting. Otherwise, the limit setting.
         * @param int|null $param2 (Optional) Limit setting if `$param1` is the offset.
         * @return $this Current instance for nested calls.
         */
        public function limit(int $param1, ?int $param2 = null){
            if(is_null($param2)){
                $this->_limit = [0, $param1];
            }else{
                $this->_limit = [$param1, $param2];
            }
            return $this;
        }

        /**
         * Performs a raw UNION between two queries.
         * @param string $query Raw query to union.
         * @param bool $all (Optional) Return all results instead of just the unique ones.
         * @return $this Current instance for nested calls.
         */
        public function rawUnion(string $query, bool $all = false){
            $type = $all ? 'UNION ALL ' : 'UNION ';
            $this->_union = $type . $query;
            return $this;
        }

        public function rawUnionAll(string $query){
            return $this->rawUnion($query, true);
        }

        /**
         * Performs an UNION between two queries.
         * @param Kraken $query Query builder instance to union.
         * @param bool $all (Optional) Return all results instead of just the unique ones.
         * @return $this Current instance for nested calls.
         */
        public function union(Kraken $query, bool $all = false){
            return $this->rawUnion($query->getQuery(), $all);
        }

        /**
         * Performs an UNION between two queries getting all results.
         * @param Kraken $query Query builder instance to union.
         * @return $this Current instance for nested calls.
         */
        public function unionAll(Kraken $query){
            return $this->rawUnion($query->getQuery(), true);
        }

        /**
         * Fetches the first result from a SELECT query.
         * @return mixed Returns the first resulting row on success or null if not found.
         * @throws QueryException Throws an exception if the query fails.
         */
        public function fetchRow(){
            return $this->execute(true, true);
        }

        /**
         * Fetches all results from a SELECT query.
         * @return array Returns an array with all resulting rows.
         * @throws QueryException Throws an exception if the query fails.
         */
        public function fetchAll(){
            return $this->execute(true, false);
        }

        /**
         * Inserts data into the table.
         * @param array $data An associative array relating fields and values to insert. Also accepts a multi-dimensional insert array.
         * @param bool $ignore (Optional) Ignore failing or existing rows while inserting data (INSERT IGNORE).
         * @param bool $replace (Optional) Replace existing rows matching the primary key or unique indexes (REPLACE).
         * @param array $onDuplicate (Optional) Associative array with fields and values to update on existing rows (ON DUPLICATE KEY).
         * @return bool Returns true on success or false on failure.
         * @throws QueryException Throws an exception if the query fails.
         */
        public function insert(array $data, bool $ignore = false, bool $replace = false, array $onDuplicate = []){
            // Prepares instruction
            if($replace){
                $this->_instruction = "REPLACE";
            }else{
                $type = $ignore ? ' IGNORE' : '';
                $this->_instruction = "INSERT{$type}";
            }

            // Prepares the fields and values
            $fields = [];
            $values = [];

            // Checks for multiple inserts
            if(!Util::isAssociativeArray($data)){
                // Validate multiple inserts
                if(!is_array($data[0])) throw new Exception('insert(): Multiple INSERT calls must be a multi-dimensional array');

                // Get fields
                $fields = array_keys($data[0]);

                // Get values
                foreach($data as $row){
                    $result = [];

                    // Escape values
                    foreach($row as $value){
                        if($value instanceof stdClass){
                            $result[] = $value->value;
                        }else if($value === 'NULL' || is_null($value)){
                            $result[] = 'NULL';
                        }else{
                            $result[] = "\"{$this->escape($value)}\"";
                        }
                    }
                    $result = implode(', ', $result);
                    $values[] = "({$result})";
                }
            }else{
               foreach($data as $field => $value){
                  $fields[] = $field;

                  // Escape values
                  if($value instanceof stdClass){
                    $values[] = $value->value;
                  }else if($value === 'NULL' || is_null($value)){
                    $values[] = 'NULL';
                  }else{
                    $values[] = "\"{$this->escape($value)}\"";
                  }
               }
               $values = implode(', ', $values);
               $values = ["({$values})"];
            }

            // Checks for ON DUPLICATE KEY statement
            if(!empty($onDuplicate)){
                $set = [];

                // Escape values
                foreach($onDuplicate as $key => $value){
                    if($value instanceof stdClass){
                        $set[] = "{$key} = {$value->value}";
                    }else if($value === 'NULL' || is_null($value)){
                        $set[] = "{$key} = NULL";
                    }else{
                        $set[] = "{$key} = \"{$this->escape($value)}\"";
                    }
                }

                $set = implode(', ', $set);
                $this->_duplicate = "ON DUPLICATE KEY UPDATE {$set}";
            }

            // Stores data to the query builder and run
            $this->_values = implode(', ', $values);
            $this->_insert = implode(', ', $fields);
            return $this->execute();
        }

        /**
         * Inserts data into the table ignoring failing or existing rows.
         * @param array $data An associative array relating fields and values to insert. Also accepts an array of multiple insert arrays.
         * @return bool Returns true on success or false on failure.
         * @throws QueryException Throws an exception if the query fails.
         */
        public function insertIgnore(array $data){
            return $this->insert($data, true);
        }

        /**
         * Inserts data into the table replacing existing rows matching the primary key or unique indexes.
         * @param array $data An associative array relating fields and values to insert. Also accepts an array of multiple insert arrays.
         * @return bool Returns true on success or false on failure.
         * @throws QueryException Throws an exception if the query fails.
         */
        public function replace(array $data){
            return $this->insert($data, false, true);
        }

        /**
         * Inserts data into the table or updates if a primary key or unique index already exists.
         * @param array $data An associative array relating fields and values to insert.
         * @param array $update An associative array relating fields and values to update if the row already exists.
         * @return bool Returns true on success or false on failure.
         * @throws QueryException Throws an exception if the query fails.
         */
        public function upsert(array $data, array $update){
            return $this->insert($data, false, false, $update);
        }

        /**
         * Updates data in the table.\
         * **Do not forget to use WHERE statements before calling this function, otherwise all records will be updated.**
         * @param array $data An associative array relating fields and values to update.
         * @return bool Returns true on success or false on failure.
         * @throws QueryException Throws an exception if the query fails.
         */
        public function update(array $data){
            // Check for safe mode
            if($this->_safe && empty($this->_where)) throw new Exception('update(): Safe mode reports missing WHERE statements before UPDATE query');

            // Set params
            $this->_instruction = 'UPDATE';
            $set = [];

            // Escape values
            foreach($data as $key => $value){
                if($value instanceof stdClass){
                    $set[] = "{$key} = {$value->value}";
                }else if($value === 'NULL' || is_null($value)){
                    $set[] = "{$key} = NULL";
                }else{
                    $set[] = "{$key} = \"{$this->escape($value)}\"";
                }
            }

            $this->_set = implode(', ', $set);
            return $this->execute();
        }

        /**
         * Deletes data from the table.\
         * **Do not forget to use WHERE statements before calling this function, otherwise all records will be deleted.**
         * @param string|array $table (Optional) Table name to delete data in case of table joins. You can also use an array of table names.
         * @return bool Returns true on success or false on failure.
         * @throws QueryException Throws an exception if the query fails.
         */
        public function delete($table = ''){
            if($this->_safe && empty($this->_where)) throw new Exception('delete(): Safe mode reports missing WHERE statements before DELETE query');
            $this->_instruction = 'DELETE';
            if(!Util::isEmpty($table)) $this->_delete = implode(', ', (array)$table);
            return $this->execute();
        }

        /**
         * Counts the number of resulting rows from a SELECT query.
         * @param string $column (Optional) Column to use as the counting base. Using `*` will count all rows including NULL values.\
         * Setting a column name will count all rows excluding NULL values from that column. You can also use a raw COUNT expression.
         * @return int Returns the number of rows on success.
         * @throws QueryException Throws an exception if the query fails.
         */
        public function count(string $column = '*'){
            // Backup return type
            $return = $this->_returnAssoc;

            // Count rows
            if($this->_instruction != 'SELECT DISTINCT') $this->_instruction = "SELECT";
            $this->_select = "COUNT({$column}) AS count";
            $result = $this->asElement()->fetchRow();

            // Restore return type
            $this->_returnAssoc = $return;

            // Returns the result
            if($result !== false){
                return (int)$result->count;
            }else{
                return $result;
            }
        }

        /**
         * Sums the value of all rows in a specific column.
         * @param string $column Column to retrieve values. You can also use a raw SUM expression.
         * @return string Returns the sum result on success.
         * @throws QueryException Throws an exception if the query fails.
         */
        public function sum(string $column){
            // Backup return type
            $return = $this->_returnAssoc;

            // Sum rows
            if($this->_instruction != 'SELECT DISTINCT') $this->_instruction = "SELECT";
            $this->_select = "SUM({$column}) AS sum";
            $result = $this->asElement()->fetchRow();

            // Restore return type
            $this->_returnAssoc = $return;

            // Returns the result
            if($result !== false){
                return $result->sum;
            }else{
                return $result;
            }
        }

        /**
         * Returns the highest value from a specific column.
         * @param string $column Column to retrieve the value. You can also use a raw MAX expression.
         * @return string Returns the highest value on success.
         * @throws QueryException Throws an exception if the query fails.
         */
        public function max(string $column){
            // Backup return type
            $return = $this->_returnAssoc;

            // Get max value
            if($this->_instruction != 'SELECT DISTINCT') $this->_instruction = "SELECT";
            $this->_select = "MAX({$column}) AS max";
            $result = $this->asElement()->fetchRow();

            // Restore return type
            $this->_returnAssoc = $return;

            // Returns the result
            if($result !== false){
                return $result->max;
            }else{
                return $result;
            }
        }

        /**
         * Returns the lowest value from a specific column.
         * @param string $column Column to retrieve the value. You can also use a raw MIN expression.
         * @return string Returns the lowest value on success.
         * @throws QueryException Throws an exception if the query fails.
         */
        public function min(string $column){
            // Backup return type
            $return = $this->_returnAssoc;

            // Get min value
            if($this->_instruction != 'SELECT DISTINCT') $this->_instruction = "SELECT";
            $this->_select = "MIN({$column}) AS min";
            $result = $this->asElement()->fetchRow();

            // Restore return type
            $this->_returnAssoc = $return;

            // Returns the result
            if($result !== false){
                return $result->min;
            }else{
                return $result;
            }
        }

        /**
         * Returns the average value from a specific column.
         * @param string $column Column to retrieve the value. You can also use a raw AVG expression.
         * @return string Returns the average value on success.
         * @throws QueryException Throws an exception if the query fails.
         */
        public function avg(string $column){
            // Backup return type
            $return = $this->_returnAssoc;

            // Get avg value
            if($this->_instruction != 'SELECT DISTINCT') $this->_instruction = "SELECT";
            $this->_select = "AVG({$column}) AS avg";
            $result = $this->asElement()->fetchRow();

            // Restore return type
            $this->_returnAssoc = $return;

            // Returns the result
            if($result !== false){
                return $result->avg;
            }else{
                return $result;
            }
        }

        /**
         * Checks if there are any records that match a SELECT query.
         * @return bool Returns true if exists or false if not.
         * @throws QueryException Throws an exception if the query fails.
         */
        public function exists(){
            $result = $this->count();
            return (is_int($result) && $result >= 1);
        }

        /**
         * Checks if there are not any records that match a SELECT query.
         * @return bool Returns true if does not exist or false if it does.
         * @throws QueryException Throws an exception if the query fails.
         */
        public function doesntExist(){
            return !$this->exists();
        }

        /**
         * Fetches all results from a SELECT query with pagination.
         * @param int $currentPage (Optional) Current page to get results.
         * @param int $resultsPerPage (Optional) Number of results to get per page.
         * @return Element Returns an Element with the pagination result.
         * @throws QueryException Throws an exception if the query fails.
         */
        public function paginate(int $currentPage = 1, int $resultsPerPage = 25){
            // Backup query
            $query = $this->backupQuery();

            // Counts total pages
            $this->_limit = [];
            $totalResults = $this->count();
            $totalPages = floor($totalResults / $resultsPerPage);
            if($totalResults % $resultsPerPage != 0) $totalPages++;

            // Restore query
            $this->restoreQuery($query);

            // Gets paginated results
            $offset = ($currentPage - 1) * $resultsPerPage;
            $this->limit($offset, $resultsPerPage);
            $results = $this->fetchAll();

            // Parse results
            return new Element([
                'page' => $currentPage,
                'is_valid' => !empty($results),
                'data' => $results,
                'from' => empty($results) ? 0 : $offset + 1,
                'to' => empty($results) ? 0 : count($results) + $offset,
                'total_pages' => (int)$totalPages,
                'previous_page' => $currentPage == 1 ? $currentPage : $currentPage - 1,
                'next_page' => $currentPage == $totalPages ? $currentPage : $currentPage + 1,
                'results_per_page' => $resultsPerPage,
                'total_results' => $totalResults
            ]);
        }

        /**
         * Fetches all results from a SELECT query in small chunks of items.
         * @param int $items Number of items to fetch per chunk.
         * @param Closure $callback Closure to call in each chunk. Returning `false` from this closure will stop next queries.
         * @throws QueryException Throws an exception if the query fails.
         */
        public function chunk(int $items, Closure $callback){
            // Backup current query state
            $query = $this->backupQuery();

            // Counts total chunks
            $this->_limit = [];
            $totalResults = $this->count();
            $totalChunks = floor($totalResults / $items);
            if($totalResults % $items != 0) $totalChunks++;

            // Performs chunked queries
            for($i = 0; $i < $totalChunks; $i++){
                // Restore query state
                $this->restoreQuery($query);

                // Gets results
                $this->limit($i * $items, $items);
                $results = $this->fetchAll();

                // Calls the closure and stores the return
                $return = call_user_func_array($callback, [$results]);

                // If the closure returns false, break the loop
                if($return === false) break;
            }
        }

        /**
         * Returns the last inserted `AUTO_INCREMENT` value from an INSERT query.
         * @return mixed Last insert id.
         */
        public function lastInsertId(){
            return $this->_lastInsertId;
        }

        /**
         * Returns the number of affected rows from an UPDATE or INSERT query.
         * @return int Number of affected rows.
         */
        public function affectedRows(){
            return $this->getConnection()->affected_rows;
        }

        /**
         * Clears the current built query entirely.
         * @return $this Current instance for nested calls.
         */
        public function clearQuery(){
            $this->_instruction = '';
            $this->_select = '';
            $this->_from = '';
            $this->_join = [];
            $this->_where = [];
            $this->_group = [];
            $this->_having = [];
            $this->_order = [];
            $this->_limit = [];
            $this->_delete = '';
            $this->_insert = '';
            $this->_values = '';
            $this->_duplicate = '';
            $this->_set = '';
            $this->_raw = '';
            $this->_union = '';
            $this->_prepared = [];
            return $this;
        }

         /**
         * Backup the current query parameters to an array.
         * @return array Array with the current query parameters.
         */
        private function backupQuery(){
            return get_object_vars($this);
        }

        /**
         * Restores previously saved query parameters.
         * @param array $params Query parameters to restore.
         */
        private function restoreQuery(array $params){
            foreach($params as $key => $value) $this->{$key} = $value;
        }

        /**
         * Returns the current built query.
         * @return string Current built query.
         */
        public function getQuery(){
            // Checks for raw query
            if(!Util::isEmpty($this->_raw)) return $this->_raw;

            // Checks for empty query
            if(Util::isEmpty($this->_instruction)) $this->_instruction = 'SELECT';
            if(Util::isEmpty($this->_select)) $this->_select = '*';

            // Gets the instruction
            $query = $this->_instruction;

            // Gets SELECT statement
            if($this->_instruction == 'SELECT' || $this->_instruction == 'SELECT DISTINCT'){
                $query .= " {$this->_select}";
            }

            // Gets DELETE statement
            if(!Util::isEmpty($this->_delete)){
                $query .= " {$this->_delete}";
            }

            // Gets FROM statement
            if($this->_instruction == 'SELECT' || $this->_instruction == 'SELECT DISTINCT' || $this->_instruction == 'DELETE'){
                if (!Util::isEmpty($this->_from)) {
                    $query .= " FROM {$this->_from}";
                } else {
                    $query .= " FROM {$this->_table}";
                }
            }

            // Gets UPDATE statements
            if($this->_instruction == 'UPDATE'){
                $query .= " {$this->_table}";
            }

            // Gets JOIN statements
            if(!empty($this->_join)){
                $join = implode(' ', $this->_join);
                $query .= " {$join}";
            }

            // Gets SET statements
            if($this->_instruction == 'UPDATE'){
                $query .= " SET {$this->_set}";
            }

            // Gets INSERT statements
            if($this->_instruction == 'INSERT' || $this->_instruction == 'INSERT IGNORE' || $this->_instruction == 'REPLACE'){
                if(!Util::isEmpty($this->_insert) && !Util::isEmpty($this->_values)){
                    $query .= " INTO {$this->_table} ({$this->_insert}) VALUES $this->_values";
                }
            }

            // Gets ON DUPLICATE KEY statement
            if($this->_instruction == 'INSERT'){
                if (!Util::isEmpty($this->_duplicate)) {
                    $query .= " {$this->_duplicate}";
                }
            }

            // Gets WHERE statements
            if($this->_instruction == 'SELECT'|| $this->_instruction == 'SELECT DISTINCT' || $this->_instruction == 'UPDATE' || $this->_instruction == 'DELETE'){
                if(!empty($this->_where)){
                    $where = implode(' ', $this->_where);
                    $query .= " WHERE {$where}";
                }
            }

            // Gets UNION statement
            if(!empty($this->_union)){
                $query .= " {$this->_union}";
            }

            // Gets GROUP BY, HAVING and ORDER BY statements
            if($this->_instruction == 'SELECT' || $this->_instruction == 'SELECT DISTINCT'){
                if(!empty($this->_group)){
                    $group = implode(', ', $this->_group);
                    $query .= " GROUP BY {$group}";
                }

                if(!empty($this->_having)){
                    $having = implode(' ', $this->_having);
                    $query .= " HAVING {$having}";
                }

                if(!empty($this->_order)){
                    $order = implode(', ', $this->_order);
                    $query .= " ORDER BY {$order}";
                }
            }

            // Gets LIMIT statement
            if($this->_instruction == 'SELECT' || $this->_instruction == 'SELECT DISTINCT'){
                if (!empty($this->_limit)) {
                    $limit = implode(', ', $this->_limit);
                    $query .= " LIMIT {$limit}";
                }
            }else if($this->_instruction == 'UPDATE' || $this->_instruction == 'DELETE'){
                if (!empty($this->_limit)) {
                    $query .= " LIMIT {$this->_limit[1]}";
                }
            }

            // Returns the result
            return $query;
        }

    }

?>