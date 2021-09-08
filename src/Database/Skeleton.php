<?php
    namespace Glowie\Core\Database;

    use stdClass;
    use Glowie\Core\Traits\DatabaseTrait;
    use Glowie\Core\Exception\QueryException;

    /**
     * Database schema manager for Glowie application.
     * @category Database
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.0
     */
    class Skeleton{
        use DatabaseTrait;

        /**
         * EXISTS instruction.
         * @var string
         */
        private $_exists;

        /**
         * Table fields.
         * @var array
         */
        private $_fields;

        /**
         * Table primary keys.
         * @var array
         */
        private $_primary;

        /**
         * Table unique indexes.
         * @var array
         */
        private $_unique;

        /**
         * Table indexes.
         * @var array
         */
        private $_key;

        /**
         * RENAME table statement.
         * @var string
         */
        private $_rename;

        /**
         * Creates a new Skeleton database instance.
         * @param string $table (Optional) Table name to set as default.
         * @param array $database (Optional) Associative array with the database connection settings.\
         * Use an empty array to connect to the environment defined database (from **app/config/Config.php**).
         * @param string $charset (Optional) Database character set encoding to use.
         */
        public function __construct(string $table = 'glowie', array $database = [], string $charset = 'utf8'){
            $this->database($database);
            $this->table($table);
            $this->charset($charset);
        }

        /**
         * Adds a field to the table blueprint.
         * @param string $name Field name to add.
         * @param string $type Field data type. Must be a valid type supported by your current MySQL version.
         * @param int|null $size (Optional) Field maximum length.
         * @param bool $nullable (Optional) Set to **true** if the field should accept `NULL` values.
         * @param mixed $default (Optional) Default field value.
         * @param bool $autoincrement (Optional) Set to **true** to auto increment the field value.
         * @return Skeleton Current Skeleton instance for nested calls.
         */
        public function addField(string $name, string $type, $size = null, bool $nullable = true, $default = null, bool $autoincrement = false){
            // Field name
            $field = "`{$name}` ";

            // Field type and size
            $field .= strtoupper($type);
            if(!empty($size)) $field .= "({$size})";

            // Not nullable field
            if(!$nullable) $field .= " NOT NULL";

            // Default value
            if($default !== null){
                if ($default instanceof stdClass) {
                    $field .= " DEFAULT {$default->value}";
                } else {
                    $field .= " DEFAULT \"{$this->escape($default)}\"";
                }
            }else if($autoincrement){
                $field .= " AUTO_INCREMENT";
            }

            // Saves the result
            $this->_fields[] = $field;
            return $this;
        }

        /**
         * Adds a raw field to the table blueprint.
         * @return Skeleton Current Skeleton instance for nested calls.
         */
        public function rawAddField(string $field){
            $this->_fields[] = $field;
            return $this;
        }

        /**
         * Adds a table field to a PRIMARY KEY.
         * @param string|array $field A single field name or an array of fields to add to the primary key.
         * @return Skeleton Current Skeleton instance for nested calls.
         */
        public function primaryKey($field){
            foreach((array)$field as $item){
                $this->_primary[] = $item;
            }
            return $this;
        }

        /**
         * Adds a table field to an INDEX key.
         * @param string|array $field A single field name or an array of fields to add to the key.
         * @param string $name (Optional) The key name. Leave empty to assign each field to its own key.
         * @param bool $unique (Optional) Mark the key as an UNIQUE INDEX.
         * @return Skeleton Current Skeleton instance for nested calls.
         */
        public function key($field, string $name = '', bool $unique = false){
            foreach((array)$field as $item){
                $key = $name;
                if(empty($name)) $key = $item;
                if($unique){
                    $this->_unique[$key][] = "`{$item}`";
                }else{
                    $this->_key[$key][] = "`{$item}`";
                }
            }
            return $this;
        }

        /**
         * Adds a table field to an UNIQUE INDEX key.
         * @param string|array $field A single field name or an array of fields to add to the key.
         * @param string $name (Optional) The key name. Leave empty to assign each field to its own key.
         * @return Skeleton Current Skeleton instance for nested calls.
         */
        public function unique($field, string $name = ''){
            return $this->key($field, $name, true);
        }

        /**
         * Adds an IF EXISTS statement to the query.
         * @return Skeleton Current Skeleton instance for nested calls.
         */
        public function ifExists(){
            $this->_exists = ' IF EXISTS';
            return $this;
        }

        /**
         * Adds an IF NOT EXISTS statement to the query.
         * @return Skeleton Current Skeleton instance for nested calls.
         */
        public function ifNotExists(){
            $this->_exists = ' IF NOT EXISTS';
            return $this;
        }

        /**
         * Creates a new table.\
         * **Important:** The table must have at least one field.
         * @return bool Returns true on success.
         * @throws QueryException Throws an exception if the query fails.
         */
        public function create(){
            $this->_instruction = 'CREATE TABLE';
            return $this->execute();
        }

        /**
         * Changes the table name.
         * @param string $name New table name to rename to.
         * @return bool Returns true on success.
         * @throws QueryException Throws an exception if the query fails.
         */
        public function rename(string $name){
            $this->_instruction = 'RENAME TABLE';
            $this->_rename = $name;
            return $this->execute();
        }

        /**
         * Cleans the whole data from the table.
         * @return bool Returns true on success.
         * @throws QueryException Throws an exception if the query fails.
         */
        public function truncate(){
            $this->_instruction = 'TRUNCATE TABLE';
            return $this->execute();
        }

        /**
         * Deletes the table from the database.
         * @return bool Returns true on success.
         * @throws QueryException Throws an exception if the query fails.
         */
        public function drop(){
            $this->_instruction = 'DROP TABLE';
            return $this->execute();
        }

        /**
         * Clears the current built query entirely.
         */
        public function clearQuery(){
            $this->_instruction = '';
            $this->_exists = '';
            $this->_fields = [];
            $this->_primary = [];
            $this->_unique = [];
            $this->_key = [];
            $this->_rename = '';
            $this->_raw = '';
        }

        /**
         * Returns the current built query.
         * @return string Current built query.
         */
        public function getQuery(){
            // Checks for raw query
            if(!empty($this->_raw)) return $this->_raw;

            // Gets the instruction
            $query = $this->_instruction;

            // Gets EXISTS
            if($this->_instruction == 'CREATE TABLE' || $this->_instruction == 'TRUNCATE TABLE' || $this->_instruction == 'DROP TABLE'){
                if(!empty($this->_exists)){
                    $query .= $this->_exists;
                }
            }

            // Gets the table
            $query .= " `{$this->_table}`";

            // Gets CREATE TABLE parameters
            if($this->_instruction == 'CREATE TABLE'){
                // Opening parenthesis
                $instructions = [];
                $query .= ' (';

                // Fields
                if (!empty($this->_fields)) $instructions = $this->_fields;

                // Primary keys
                if(!empty($this->_primary)){
                    $primary = implode('`, `', $this->_primary);
                    $instructions[] = "PRIMARY KEY (`{$primary}`)";
                }

                // Unique indexes
                if(!empty($this->_unique)){
                    foreach($this->_unique as $name => $unique){
                        $fields = implode(', ', $unique);
                        $instructions[] = "UNIQUE INDEX `{$name}` ({$fields})";
                    }
                }

                // Keys
                if(!empty($this->_key)){
                    foreach($this->_key as $name => $key){
                        $fields = implode(', ', $key);
                        $instructions[] = "INDEX `{$name}` ({$fields})";
                    }
                }

                // Creates the instruction
                $query .= implode(', ', $instructions);

                // Closing parenthesis
                $query .= ')';
            }

            // Gets RENAME parameters
            if($this->_instruction == 'RENAME TABLE'){
                $query .= " TO `{$this->_rename}`";
            }

            // Returns the result
            return $query;
        }

    }

?>