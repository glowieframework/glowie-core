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
     * @version 1.2
     */
    class Skeleton{
        use DatabaseTrait;

        /**
         * String data type.
         * @var string
         */
        public const TYPE_STRING = 'VARCHAR';

        /**
         * Text data type.
         * @var string
         */
        public const TYPE_TEXT = 'TEXT';

        /**
         * Tiny text data type
         * @var string
         */
        public const TYPE_TINY_TEXT = 'TINYTEXT';

        /**
         * Long text data type.
         * @var string
         */
        public const TYPE_LONG_TEXT = 'LONGTEXT';

        /**
         * Integer data type.
         * @var string
         */
        public const TYPE_INTEGER = 'INT';

        /**
         * Tiny integer data type.
         * @var string
         */
        public const TYPE_TINY_INTEGER = 'TINYINT';

        /**
         * Big integer data type.
         * @var string
         */
        public const TYPE_BIG_INTEGER = 'BIGINT';

        /**
         * Float data type.
         * @var string
         */
        public const TYPE_FLOAT = 'FLOAT';

        /**
         * Double data type.
         * @var string
         */
        public const TYPE_DOUBLE = 'DOUBLE';

        /**
         * Date data type.
         * @var string
         */
        public const TYPE_DATE = 'DATE';

        /**
         * Time data type.
         * @var string
         */
        public const TYPE_TIME = 'TIME';

        /**
         * Datetime data type.
         * @var string
         */
        public const TYPE_DATETIME = 'DATETIME';

        /**
         * Timestamp data type.
         * @var string
         */
        public const TYPE_TIMESTAMP = 'TIMESTAMP';

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
         * Table COLLATE.
         * @var string
         */
        private $_collate;

        /**
         * AUTO_INCREMENT field.
         * @var string
         */
        private $_autoincrement;

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
        private $_index;

        /**
         * Table foreign keys.
         * @var array
         */
        private $_foreign;

        /**
         * Key drops.
         * @var array
         */
        private $_drops;

        /**
         * RENAME table statement.
         * @var string
         */
        private $_rename;

        /**
         * LIKE statement.
         * @var string
         */
        private $_like;

        /**
         * Database name.
         * @var string
         */
        private $_database;

        /**
         * Creates a new Skeleton database instance.
         * @param string $table (Optional) Table name to set as default.
         * @param string $database (Optional) Database connection name (from your app configuration).
         */
        public function __construct(string $table = 'glowie', string $database = 'default'){
            $this->table($table);
            $this->database($database);
        }

        /**
         * Creates a column in a new table.
         * @param string $name Column name to create.
         * @param string $type Column data type. Must be a valid type supported by your current MySQL version.
         * @param int|null $size (Optional) Field maximum length.
         * @param mixed $default (Optional) Default field value.
         * @return Skeleton Current Skeleton instance for nested calls.
         */
        public function createColumn(string $name, string $type, $size = null, $default = null){
            return $this->modifyColumns([
                'operation' => 'create',
                'name' => $name,
                'type' => $type,
                'size' => $size,
                'default' => $default,
                'nullable' => false
            ]);
        }

        /**
         * Creates a nullable column in a new table.
         * @param string $name Column name to create.
         * @param string $type Column data type. Must be a valid type supported by your current MySQL version.
         * @param int|null $size (Optional) Field maximum length.
         * @param mixed $default (Optional) Default field value.
         * @return Skeleton Current Skeleton instance for nested calls.
         */
        public function createNullableColumn(string $name, string $type, ?int $size = null, $default = null){
            return $this->modifyColumns([
                'operation' => 'create',
                'name' => $name,
                'type' => $type,
                'size' => $size,
                'default' => $default,
                'nullable' => true
            ]);
        }

        /**
         * Creates/adds an AUTO_INCREMENT column in the table.\
         * **Important:** This column must also be set as a key.
         * @param string $name Column name to create.
         * @param string $type (Optional) Column data type. Must be a valid type supported by your current MySQL version.
         * @param int|null $size (Optional) Field maximum length.
         * @return Skeleton Current Skeleton instance for nested calls.
         */
        public function autoIncrement(string $name, string $type = self::TYPE_BIG_INTEGER, ?int $size = null){
            $type = strtoupper($type);
            $field = "`{$name}` {$type}";
            if(!empty($size)) $field .= "({$size})";
            $this->_autoincrement = "{$field} NOT NULL";
            return $this;
        }

        /**
         * Creates timestamp fields in the table.
         * @param string $createdField (Optional) **Created at** field name.
         * @param string $updatedField (Optional) **Updated at** field name.
         * @return Skeleton Current Skeleton instance for nested calls.
         */
        public function createTimestamps(string $createdField = 'created_at', string $updatedField = 'updated_at'){
            $this->createColumn($createdField, self::TYPE_DATETIME, null, self::raw('NOW()'));
            $this->createColumn($updatedField, self::TYPE_DATETIME, null, self::raw('NOW()'));
            return $this;
        }

        /**
         * Adds timestamp fields into an existing table.
         * @param string $createdField (Optional) **Created at** field name.
         * @param string $updatedField (Optional) **Updated at** field name.
         * @return Skeleton Current Skeleton instance for nested calls.
         */
        public function addTimestamps(string $createdField = 'created_at', string $updatedField = 'updated_at'){
            $this->addColumn($createdField, self::TYPE_DATETIME, null, self::raw('NOW()'));
            $this->addColumn($updatedField, self::TYPE_DATETIME, null, self::raw('NOW()'));
            return $this;
        }

        /**
         * Creates/adds an **id** column (auto increment) in the table and set it as the primary key.
         * @param string $name (Optional) Column name.
         * @return Skeleton Current Skeleton instance for nested calls.
         */
        public function id(string $name = 'id'){
            $this->autoIncrement($name);
            $this->primaryKey($name);
            return $this;
        }

        /**
         * Adds a column into an existing table.
         * @param string $name Column name to add.
         * @param string $type Column data type. Must be a valid type supported by your current MySQL version.
         * @param int|null $size (Optional) Field maximum length.
         * @param mixed $default (Optional) Default field value.
         * @param string|null $after (Optional) Name of other column to place this column after it.
         * @return Skeleton Current Skeleton instance for nested calls.
         */
        public function addColumn(string $name, string $type, ?int $size = null, $default = null, ?string $after = null){
            return $this->modifyColumns([
                'operation' => 'add',
                'name' => $name,
                'type' => $type,
                'size' => $size,
                'default' => $default,
                'after' => $after,
                'nullable' => false
            ]);
        }

        /**
         * Adds a nullable column to an existing table.
         * @param string $name Column name to add.
         * @param string $type Column data type. Must be a valid type supported by your current MySQL version.
         * @param int|null $size (Optional) Field maximum length.
         * @param mixed $default (Optional) Default field value.
         * @param string|null $after (Optional) Name of other column to place this column after it.
         * @return Skeleton Current Skeleton instance for nested calls.
         */
        public function addNullableColumn(string $name, string $type, ?int $size = null, $default = null, ?string $after = null){
            return $this->modifyColumns([
                'operation' => 'add',
                'name' => $name,
                'type' => $type,
                'size' => $size,
                'default' => $default,
                'after' => $after,
                'nullable' => true
            ]);
        }

        /**
         * Changes an existing column in a table.
         * @param string $name Column name to change.
         * @param string $new_name New column name.
         * @param string $type Column data type. Must be a valid type supported by your current MySQL version.
         * @param int|null $size (Optional) Field maximum length.
         * @param bool $nullable (Optional) Set to **true** if the field should accept `NULL` values.
         * @param mixed $default (Optional) Default field value.
         * @param string|null $after (Optional) Name of other column to move this column below it.
         * @return Skeleton Current Skeleton instance for nested calls.
         */
        public function changeColumn(string $name, string $new_name, string $type, ?int $size = null, bool $nullable = true, $default = null, ?string $after = null){
            return $this->modifyColumns([
                'operation' => 'change',
                'name' => $name,
                'new_name' => $new_name,
                'type' => $type,
                'size' => $size,
                'nullable' => $nullable,
                'default' => $default,
                'after' => $after
            ]);
        }

        /**
         * Deletes an existing column from a table.
         * @param string $name Column name to drop.
         * @return Skeleton Current Skeleton instance for nested calls.
         */
        public function dropColumn(string $name){
            return $this->modifyColumns([
                'operation' => 'drop',
                'name' => $name
            ]);
        }

        /**
         * Deletes timestamp fields from a table.
         * @param string $createdField (Optional) **Created at** field name.
         * @param string $updatedField (Optional) **Updated at** field name.
         * @return Skeleton Current Skeleton instance for nested calls.
         */
        public function dropTimestamps(string $createdField = 'created_at', string $updatedField = 'updated_at'){
            $this->dropColumn($createdField);
            $this->dropColumn($updatedField);
            return $this;
        }

        /**
         * Parse column operations.
         * @param array $data Associative array of data to parse.
         * @return Skeleton Current Skeleton instance for nested calls.
         */
        private function modifyColumns(array $data){
            // Column name
            switch($data['operation']){
                case 'create':
                    $field = "`{$data['name']}` ";
                    break;

                case 'change':
                    $field = "CHANGE COLUMN `{$data['name']}` `{$data['new_name']}` ";
                    break;

                case 'add':
                    $field = "ADD COLUMN `{$data['name']}` ";
                    break;

                case 'drop':
                    $field = "DROP COLUMN `{$data['name']}`";
                    $this->_fields[] = $field;
                    return $this;
            }

            // Field type and size
            $field .= strtoupper($data['type']);
            if(!empty($data['size'])) $field .= "({$data['size']})";

            // Not nullable field
            if(!$data['nullable']) $field .= " NOT NULL";

            // Default value
            if($data['default'] !== null){
                if ($data['default'] instanceof stdClass) {
                    $field .= " DEFAULT {$data['default']->value}";
                } else {
                    $field .= " DEFAULT \"{$this->escape($data['default'])}\"";
                }
            }

            // After
            if($data['operation'] != 'create'){
                if(!empty($data['after'])) $field .= " AFTER `{$data['after']}`";
            }

            // Saves the result
            $this->_fields[] = $field;
            return $this;
        }

        /**
         * Adds a table column to a PRIMARY KEY.
         * @param string|array $column A single column name or an array of columns to add to the primary key.
         * @return Skeleton Current Skeleton instance for nested calls.
         */
        public function primaryKey($column){
            foreach((array)$column as $item){
                $this->_primary[] = $item;
            }
            return $this;
        }

        /**
         * Adds a table column to an INDEX key.
         * @param string|array $column A single column name or an array of columns to add to the key.
         * @param string $name (Optional) The key name. Leave empty to assign each field to its own key.
         * @param bool $unique (Optional) Mark the key as an UNIQUE INDEX.
         * @return Skeleton Current Skeleton instance for nested calls.
         */
        public function index($column, string $name = '', bool $unique = false){
            foreach((array)$column as $item){
                $key = $name;
                if(empty($name)) $key = $item;
                if($unique){
                    $this->_unique[$key][] = "`{$item}`";
                }else{
                    $this->_index[$key][] = "`{$item}`";
                }
            }
            return $this;
        }

        /**
         * Adds a table column to an UNIQUE INDEX key.
         * @param string|array $column A single column name or an array of columns to add to the key.
         * @param string $name (Optional) The key name. Leave empty to assign each field to its own key.
         * @return Skeleton Current Skeleton instance for nested calls.
         */
        public function unique($column, string $name = ''){
            return $this->index($column, $name, true);
        }

        /**
         * Adds a FOREIGN KEY constraint to the table.
         * @param string|array $column A single column name or an array of columns to add to the constraint.
         * @param string $table The referenced table name.
         * @param string|array $reference A single referenced column name or an array of referenced columns.
         * @param string|null $name (Optional) Constraint name. If defined, **must be unique in the database**.
         * @param string $update (Optional) Referential action on parent table UPDATE queries.\
         * Valid options are: `CASCADE`, `SET NULL`, `RESTRICT`, `NO ACTION` or `SET DEFAULT`.
         * @param string $delete (Optional) Referential action on parent table DELETE queries.\
         * Valid options are: `CASCADE`, `SET NULL`, `RESTRICT`, `NO ACTION` or `SET DEFAULT`.
         * @return Skeleton Current Skeleton instance for nested calls.
         */
        public function foreignKey($column, string $table, $reference, ?string $name = null, string $update = 'RESTRICT', string $delete = 'RESTRICT'){
            if(!empty($name)) $name = "CONSTRAINT `{$name}` ";
            $column = implode('`, `', (array)$column);
            $reference = implode('`, `', (array)$reference);
            $this->_foreign[] = "{$name}FOREIGN KEY (`{$column}`) REFERENCES `{$table}` (`{$reference}`) ON UPDATE {$update} ON DELETE {$delete}";
            return $this;
        }

        /**
         * Deletes an existing primary key from the table.
         * @return Skeleton Current Skeleton instance for nested calls.
         */
        public function dropPrimaryKey(){
            $this->_drops[] = "DROP PRIMARY KEY";
            return $this;
        }

        /**
         * Deletes an existing INDEX key from the table.
         * @param string $name The key name to drop.
         * @return Skeleton Current Skeleton instance for nested calls.
         */
        public function dropIndex(string $name){
            $this->_drops[] = "DROP INDEX `{$name}`";
            return $this;
        }

        /**
         * Deletes an existing UNIQUE INDEX key from the table.
         * @param string $name The key name to drop.
         * @return Skeleton Current Skeleton instance for nested calls.
         */
        public function dropUnique(string $name){
            return $this->dropIndex($name);
        }

        /**
         * Deletes an existing FOREIGN KEY constraint from the table.
         * @param string $name The key name to drop.
         * @return Skeleton Current Skeleton instance for nested calls.
         */
        public function dropForeignKey(string $name){
            $this->_drops[] = "DROP FOREIGN KEY `{$name}`";
            return $this;
        }

        /**
         * Renames an existing INDEX key from the table.
         * @param string $name The key name.
         * @param string $new_name The new name to set.
         * @return Skeleton Current Skeleton instance for nested calls.
         */
        public function renameIndex(string $name, string $new_name){
            $this->_drops[] = "RENAME INDEX `{$name}` TO `{$new_name}`";
            return $this;
        }

        /**
         * Adds an IF EXISTS statement to the query.
         * @return Skeleton Current Skeleton instance for nested calls.
         */
        public function ifExists(){
            $this->_exists = " IF EXISTS";
            return $this;
        }

        /**
         * Adds an IF NOT EXISTS statement to the query.
         * @return Skeleton Current Skeleton instance for nested calls.
         */
        public function ifNotExists(){
            $this->_exists = " IF NOT EXISTS";
            return $this;
        }

        /**
         * Adds a COLLATE setting to the table.
         * @param string $collate Collate to set.
         * @return Skeleton Current Skeleton instance for nested calls.
         */
        public function collate(string $collate){
            $this->_collate = $collate;
            return $this;
        }

        /**
         * Adds a LIKE statement to the query.
         * @param string $table Table name to copy.
         * @return Skeleton Current Skeleton instance for nested calls.
         */
        public function like(string $table){
            $this->_like = $table;
            return $this;
        }

        /**
         * Creates a new table.
         * @return bool Returns true on success.
         * @throws QueryException Throws an exception if the query fails.
         */
        public function create(){
            $this->_instruction = 'CREATE TABLE';
            return $this->execute();
        }

        /**
         * Creates a new temporary table for the current session.
         * @return bool Returns true on success.
         * @throws QueryException Throws an exception if the query fails.
         */
        public function createTemporary(){
            $this->_instruction = 'CREATE TEMPORARY TABLE';
            return $this->execute();
        }

        /**
         * Updates an existing table structure.
         * @return bool Returns true on success.
         * @throws QueryException Throws an exception if the query fails.
         */
        public function alter(){
            $this->_instruction = 'ALTER TABLE';
            return $this->execute();
        }

        /**
         * Changes the table name.
         * @param string $name New table name to rename to.
         * @return bool Returns true on success.
         * @throws QueryException Throws an exception if the query fails.
         */
        public function rename(string $name){
            $this->_instruction = "RENAME TABLE";
            $this->_rename = $name;
            return $this->execute();
        }

        /**
         * Cleans the whole data from the table.
         * @return bool Returns true on success.
         * @throws QueryException Throws an exception if the query fails.
         */
        public function truncate(){
            $this->_instruction = "TRUNCATE TABLE";
            return $this->execute();
        }

        /**
         * Deletes the table from the database.
         * @return bool Returns true on success.
         * @throws QueryException Throws an exception if the query fails.
         */
        public function drop(){
            $this->_instruction = "DROP TABLE";
            return $this->execute();
        }

        /**
         * Creates a new database.
         * @param string $name Database name to create.
         * @return bool Returns true on success.
         * @throws QueryException Throws an exception if the query fails.
         */
        public function createDatabase(string $name){
            $this->_instruction = 'CREATE DATABASE';
            $this->_database = $name;
            return $this->execute();
        }

        /**
         * Deletes a database.
         * @param string $name Database name to delete.
         * @return bool Returns true on success.
         * @throws QueryException Throws an exception if the query fails.
         */
        public function dropDatabase(string $name){
            $this->_instruction = 'DROP DATABASE';
            $this->_database = $name;
            return $this->execute();
        }

        /**
         * Checks if a column exists in the current table.
         * @param string $column Column name to check.
         * @return bool Returns true if the column exists, false otherwise.
         */
        public function columnExists(string $column){
            $this->_raw = "SHOW COLUMNS FROM `{$this->_table}` LIKE \"{$column}\"";
            $result = $this->execute(true, false);
            return !empty($result);
        }

        /**
         * Checks if a table exists in the current database.
         * @param string $table (Optional) Table name to check, leave empty to use the current working table.
         * @return bool Returns true if the table exists, false otherwise.
         */
        public function tableExists(string $table = ''){
            if(empty($table)) $table = $this->_table;
            $this->_raw = "SHOW TABLES LIKE \"{$table}\"";
            $result = $this->execute(true, false);
            return !empty($result);
        }

        /**
         * Clears the current built query entirely.
         */
        public function clearQuery(){
            $this->_instruction = '';
            $this->_exists = '';
            $this->_fields = [];
            $this->_collate = '';
            $this->_autoincrement = '';
            $this->_primary = [];
            $this->_unique = [];
            $this->_index = [];
            $this->_foreign = [];
            $this->_drops = [];
            $this->_rename = '';
            $this->_like = '';
            $this->_database = '';
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
            if(in_array($this->_instruction, ['CREATE TABLE', 'CREATE TEMPORARY TABLE', 'TRUNCATE TABLE', 'DROP TABLE', 'CREATE DATABASE', 'DROP DATABASE'])){
                if(!empty($this->_exists)) $query .= $this->_exists;
            }

            // Gets DATABASE statements
            if($this->_instruction == 'CREATE DATABASE' || $this->_instruction == 'DROP DATABASE'){
                $query .= " `{$this->_database}`";
                return $query;
            }

            // Gets the table
            $query .= " `{$this->_table}`";

            // Gets CREATE TABLE parameters
            if($this->_instruction == 'CREATE TABLE' || $this->_instruction == 'CREATE TEMPORARY TABLE'){
                // Opening parenthesis
                $instructions = [];
                $query .= ' (';

                // Like
                if(!empty($this->_like)) $instructions[] = "LIKE `{$this->_like}`";

                // Auto increment
                if(!empty($this->_autoincrement)) $instructions[] = "{$this->_autoincrement} AUTO_INCREMENT";

                // Fields
                if (!empty($this->_fields)) $instructions = array_merge($instructions, $this->_fields);

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

                // Indexes
                if(!empty($this->_index)){
                    foreach($this->_index as $name => $key){
                        $fields = implode(', ', $key);
                        $instructions[] = "INDEX `{$name}` ({$fields})";
                    }
                }

                // Foreign keys
                if(!empty($this->_foreign)) $instructions = array_merge($instructions, $this->_foreign);

                // Creates the instruction
                $query .= implode(', ', $instructions);

                // Closing parenthesis
                $query .= ')';

                // Collate
                if(!empty($this->_collate)){
                    $query .= " COLLATE=\"{$this->_collate}\"";
                }
            }

            // Gets ALTER TABLE parameters
            if($this->_instruction == 'ALTER TABLE'){
                $instructions = [];
                $query .= ' ';

                // Auto increment
                if(!empty($this->_autoincrement)){
                    $instructions[] = "ADD COLUMN {$this->_autoincrement} AUTO_INCREMENT";
                }

                // Fields
                if(!empty($this->_fields)) $instructions = array_merge($instructions, $this->_fields);

                // Key drops
                if(!empty($this->_drops)) $instructions = array_merge($instructions, $this->_drops);

                // Primary keys
                if(!empty($this->_primary)){
                    $primary = implode('`, `', $this->_primary);
                    $instructions[] = "ADD PRIMARY KEY (`{$primary}`)";
                }

                // Unique indexes
                if(!empty($this->_unique)){
                    foreach($this->_unique as $name => $unique){
                        $fields = implode(', ', $unique);
                        $instructions[] = "ADD UNIQUE INDEX `{$name}` ({$fields})";
                    }
                }

                // Indexes
                if(!empty($this->_index)){
                    foreach($this->_index as $name => $key){
                        $fields = implode(', ', $key);
                        $instructions[] = "ADD INDEX `{$name}` ({$fields})";
                    }
                }

                // Foreign keys
                if(!empty($this->_foreign)){
                    foreach($this->_foreign as $foreign){
                        $instructions[] = "ADD {$foreign}";
                    }
                }

                // Collate
                if(!empty($this->_collate)){
                    $instructions[] = "COLLATE=\"{$this->_collate}\"";
                }

                // Creates the instruction
                $query .= implode(', ', $instructions);
            }

            // Gets RENAME TABLE parameters
            if($this->_instruction == 'RENAME TABLE'){
                $query .= " TO `{$this->_rename}`";
                $this->table($this->_rename);
            }

            // Returns the result
            return $query;
        }

    }

?>