<?php

namespace Glowie\Core\Database;

use Exception;
use stdClass;
use Util;
use Glowie\Core\Traits\DatabaseTrait;
use Glowie\Core\Exception\QueryException;

/**
 * Database schema manager for Glowie application.
 * @category Database
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 * @see https://glowie.gabrielsilva.dev.br/docs/latest/extra/skeleton
 */
class Skeleton
{
    use DatabaseTrait;

    /**
     * String data type.
     * @var string
     */
    public const TYPE_STRING = 'VARCHAR';

    /**
     * Char data type.
     * @var string
     */
    public const TYPE_CHAR = 'CHAR';

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
     * Unsigned integer data type.
     * @var string
     */
    public const TYPE_INTEGER_UNSIGNED = 'INT UNSIGNED';

    /**
     * Tiny integer data type.
     * @var string
     */
    public const TYPE_TINY_INTEGER = 'TINYINT';

    /**
     * Unsigned tiny integer data type.
     * @var string
     */
    public const TYPE_TINY_INTEGER_UNSIGNED = 'TINYINT UNSIGNED';

    /**
     * Big integer data type.
     * @var string
     */
    public const TYPE_BIG_INTEGER = 'BIGINT';

    /**
     * Unsigned big integer data type.
     * @var string
     */
    public const TYPE_BIG_INTEGER_UNSIGNED = 'BIGINT UNSIGNED';

    /**
     * Float data type.
     * @var string
     */
    public const TYPE_FLOAT = 'FLOAT';

    /**
     * Decimal data type.
     * @var string
     */
    public const TYPE_DECIMAL = 'DECIMAL';

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
     * Blob data type.
     * @var string
     */
    public const TYPE_BLOB = 'BLOB';

    /**
     * Long blob data type.
     * @var string
     */
    public const TYPE_LONG_BLOB = 'LONGBLOB';

    /**
     * Enum data type.
     * @var string
     */
    public const TYPE_ENUM = 'ENUM';

    /**
     * Set data type.
     * @var string
     */
    public const TYPE_SET = 'SET';

    /**
     * Serial data type.
     * @var string
     */
    public const TYPE_SERIAL = 'SERIAL';

    /**
     * Big serial data type.
     * @var string
     */
    public const TYPE_BIGSERIAL = 'BIGSERIAL';

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
     * Table modifies.
     * @var array
     */
    private $_modifiers;

    /**
     * Post creation scripts.
     * @var array
     */
    private $_postCreate;

    /**
     * Creates a new Skeleton database instance.
     * @param string $table (Optional) Table name to set as default.
     * @param string $database (Optional) Database connection name (from your app configuration).
     */
    public function __construct(string $table = 'glowie', string $database = 'default')
    {
        $this->database($database);
        $this->table($table);
    }

    /**
     * Creates a column in a new table.
     * @param string $name Column name to create.
     * @param string $type (Optional) Column data type. Must be a valid type supported by your current database.
     * @param mixed $size (Optional) Field maximum length.
     * @param mixed $default (Optional) Default field value.
     * @param string|null $collation (Optional) Collation to set in the column.
     * @return Skeleton Current Skeleton instance for nested calls.
     */
    public function createColumn(string $name, string $type = self::TYPE_STRING, $size = null, $default = null, ?string $collation = null)
    {
        $this->_modifiers[] = [
            'operation' => 'create',
            'name' => $name,
            'type' => $type,
            'size' => $size,
            'default' => $default,
            'nullable' => false,
            'collation' => $collation
        ];
        return $this;
    }

    /**
     * Creates a nullable column in a new table.
     * @param string $name Column name to create.
     * @param string $type (Optional) Column data type. Must be a valid type supported by your current database.
     * @param mixed $size (Optional) Field maximum length.
     * @param mixed $default (Optional) Default field value.
     * @param string|null $collation (Optional) Collation to set in the column.
     * @return Skeleton Current Skeleton instance for nested calls.
     */
    public function createNullableColumn(string $name, string $type = self::TYPE_STRING, $size = null, $default = null, ?string $collation = null)
    {
        $this->_modifiers[] = [
            'operation' => 'create',
            'name' => $name,
            'type' => $type,
            'size' => $size,
            'default' => $default,
            'nullable' => true,
            'collation' => $collation
        ];
        return $this;
    }

    /**
     * Creates/adds an `AUTO_INCREMENT` column in the table.\
     * **Important:** This column must also be set as a key. There can be only a single auto increment row in a table.
     * @param string $name Column name to create.
     * @param string $type (Optional) Column data type. Must be a valid type supported by your current database.
     * @param mixed $size (Optional) Field maximum length.
     * @return Skeleton Current Skeleton instance for nested calls.
     */
    public function autoIncrement(string $name, string $type = self::TYPE_BIG_INTEGER_UNSIGNED, $size = null)
    {
        $name = $this->escapeIdentifier($name);
        switch ($this->getDriver()) {
            case 'sqlite':
                $query = "{$name} INTEGER PRIMARY KEY AUTOINCREMENT";
                break;
            case 'pgsql':
                $query = "{$name} BIGSERIAL";
                break;
            default:
                $type = mb_strtoupper($type);
                $field = "{$name} {$type}";
                if (!is_null($size)) $field .= "({$size})";
                $query = "{$field} NOT NULL AUTO_INCREMENT";
                break;
        }

        $this->_autoincrement = $query;
        return $this;
    }

    /**
     * Creates timestamp fields in the table.
     * @param string $createdField (Optional) **Created at** field name.
     * @param string $updatedField (Optional) **Updated at** field name.
     * @return Skeleton Current Skeleton instance for nested calls.
     */
    public function createTimestamps(string $createdField = 'created_at', string $updatedField = 'updated_at')
    {
        $this->createColumn($createdField, self::TYPE_DATETIME, null, self::raw('CURRENT_TIMESTAMP'));
        $this->createColumn($updatedField, self::TYPE_DATETIME, null, self::raw('CURRENT_TIMESTAMP'));
        return $this;
    }

    /**
     * Adds timestamp fields into an existing table.
     * @param string $createdField (Optional) **Created at** field name.
     * @param string $updatedField (Optional) **Updated at** field name.
     * @return Skeleton Current Skeleton instance for nested calls.
     */
    public function addTimestamps(string $createdField = 'created_at', string $updatedField = 'updated_at')
    {
        $this->addColumn($createdField, self::TYPE_DATETIME, null, self::raw('CURRENT_TIMESTAMP'));
        $this->addColumn($updatedField, self::TYPE_DATETIME, null, self::raw('CURRENT_TIMESTAMP'));
        return $this;
    }

    /**
     * Creates soft deletes field in the table.
     * @param string $field (Optional) **Deleted at** field name.
     * @return Skeleton Current Skeleton instance for nested calls.
     */
    public function createSoftDeletes(string $field = 'deleted_at')
    {
        $this->createNullableColumn($field, self::TYPE_DATETIME);
        return $this;
    }

    /**
     * Adds soft deletes field into an existing table.
     * @param string $field (Optional) **Deleted at** field name.
     * @return Skeleton Current Skeleton instance for nested calls.
     */
    public function addSoftDeletes(string $field = 'deleted_at')
    {
        $this->addNullableColumn($field, self::TYPE_DATETIME);
        return $this;
    }

    /**
     * Creates/adds an **id** column (auto increment) in the table and set it as the primary key.
     * @param string $name (Optional) Column name.
     * @return Skeleton Current Skeleton instance for nested calls.
     */
    public function id(string $name = 'id')
    {
        $this->autoIncrement($name);
        if ($this->getDriver() !== 'sqlite') $this->primaryKey($name);
        return $this;
    }

    /**
     * Creates an **id** column in UUID format in the table and set is as the primary key.
     * @param string $name (Optional) Column name.
     * @return Skeleton Current Skeleton instance for nested calls.
     */
    public function uuid(string $name = 'id')
    {
        $this->createColumn($name, self::TYPE_CHAR, 36);
        $this->primaryKey($name);
        return $this;
    }

    /**
     * Creates and adds a FOREIGN KEY column in the table referencing another model id.
     * @param string $model Related model classname with namespace. You can use `ModelName::class` to get this property correctly.
     * @param string|null $column (Optional) Column name to create in the table. Leave empty for auto.
     * @param string|null $name (Optional) Constraint name. If defined, **must be unique in the database**.
     * @param bool $nullable (Optional) If the column accepts NULL values.
     * @param string $update (Optional) Referential action on parent table UPDATE queries.\
     * Valid options are: `CASCADE`, `SET NULL`, `RESTRICT`, `NO ACTION` or `SET DEFAULT`.
     * @param string $delete (Optional) Referential action on parent table DELETE queries.\
     * Valid options are: `CASCADE`, `SET NULL`, `RESTRICT`, `NO ACTION` or `SET DEFAULT`.
     * @return Skeleton Current Skeleton instance for nested calls.
     */
    public function foreignIdFor(string $model, ?string $column = null, ?string $name = null, bool $nullable = false, string $update = 'RESTRICT', string $delete = 'RESTRICT')
    {
        // Get primary key and table names
        $reference = (new $model([], false));
        $primary = $reference->getPrimaryName();
        $table = $reference->getTable();
        if (Util::isEmpty($column)) $column = Util::snakeCase(Util::singularize(Util::classname($model))) . '_' . $primary;

        // Create column
        if ($nullable) {
            $this->createNullableColumn($column, self::TYPE_BIG_INTEGER_UNSIGNED);
        } else {
            $this->createColumn($column, self::TYPE_BIG_INTEGER_UNSIGNED);
        }

        // Create foreign key
        $this->foreignKey($column, $table, $primary, $name, $update, $delete);
        return $this;
    }

    /**
     * Creates and adds a FOREIGN KEY column in the table referencing another model UUID.
     * @param string $model Related model classname with namespace. You can use `ModelName::class` to get this property correctly.
     * @param string|null $column (Optional) Column name to create in the table. Leave empty for auto.
     * @param string|null $name (Optional) Constraint name. If defined, **must be unique in the database**.
     * @param bool $nullable (Optional) If the column accepts NULL values.
     * @param string $update (Optional) Referential action on parent table UPDATE queries.\
     * Valid options are: `CASCADE`, `SET NULL`, `RESTRICT`, `NO ACTION` or `SET DEFAULT`.
     * @param string $delete (Optional) Referential action on parent table DELETE queries.\
     * Valid options are: `CASCADE`, `SET NULL`, `RESTRICT`, `NO ACTION` or `SET DEFAULT`.
     * @return Skeleton Current Skeleton instance for nested calls.
     */
    public function foreignUuidFor(string $model, ?string $column = null, ?string $name = null, bool $nullable = false, string $update = 'RESTRICT', string $delete = 'RESTRICT')
    {
        // Get primary key and table names
        $reference = (new $model([], false));
        $primary = $reference->getPrimaryName();
        $table = $reference->getTable();
        if (Util::isEmpty($column)) $column = Util::snakeCase(Util::singularize(Util::classname($model))) . '_' . $primary;

        // Create column
        if ($nullable) {
            $this->createNullableColumn($column, self::TYPE_CHAR, 36);
        } else {
            $this->createColumn($column, self::TYPE_CHAR, 36);
        }

        // Create foreign key
        $this->foreignKey($column, $table, $primary, $name, $update, $delete);
        return $this;
    }

    /**
     * Adds a column into an existing table.
     * @param string $name Column name to add.
     * @param string $type (Optional) Column data type. Must be a valid type supported by your current database.
     * @param mixed $size (Optional) Field maximum length.
     * @param mixed $default (Optional) Default field value.
     * @param string|null $after (Optional) Name of other column to place this column after it.
     * @param string|null $collation (Optional) Collation to set in the column.
     * @return Skeleton Current Skeleton instance for nested calls.
     */
    public function addColumn(string $name, string $type = self::TYPE_STRING, $size = null, $default = null, ?string $after = null, ?string $collation = null)
    {
        $this->_modifiers[] = [
            'operation' => 'add',
            'name' => $name,
            'type' => $type,
            'size' => $size,
            'default' => $default,
            'after' => $after,
            'nullable' => false,
            'collation' => $collation
        ];
        return $this;
    }

    /**
     * Adds a nullable column to an existing table.
     * @param string $name Column name to add.
     * @param string $type (Optional) Column data type. Must be a valid type supported by your current database.
     * @param mixed $size (Optional) Field maximum length.
     * @param mixed $default (Optional) Default field value.
     * @param string|null $after (Optional) Name of other column to place this column after it.
     * @param string|null $collation (Optional) Collation to set in the column.
     * @return Skeleton Current Skeleton instance for nested calls.
     */
    public function addNullableColumn(string $name, string $type = self::TYPE_STRING, $size = null, $default = null, ?string $after = null, ?string $collation = null)
    {
        $this->_modifiers[] = [
            'operation' => 'add',
            'name' => $name,
            'type' => $type,
            'size' => $size,
            'default' => $default,
            'after' => $after,
            'nullable' => true,
            'collation' => $collation
        ];
        return $this;
    }

    /**
     * Changes an existing column in a table.
     * @param string $name Column name to change.
     * @param string|null $new_name (Optional) New column name. Leave empty for keeping the current name.
     * @param string $type (Optional) Column data type. Must be a valid type supported by your current database.
     * @param mixed $size (Optional) Field maximum length.
     * @param bool $nullable (Optional) Set to **true** if the field should accept `NULL` values.
     * @param mixed $default (Optional) Default field value.
     * @param string|null $after (Optional) Name of other column to move this column below it.
     * @param string|null $collation (Optional) Collation to set in the column.
     * @return Skeleton Current Skeleton instance for nested calls.
     */
    public function changeColumn(string $name, ?string $new_name = null, string $type = self::TYPE_STRING, $size = null, bool $nullable = true, $default = null, ?string $after = null, ?string $collation = null)
    {
        $this->_modifiers[] = [
            'operation' => (is_null($new_name) || $name === $new_name) ? 'modify' : 'change',
            'name' => $name,
            'new_name' => $new_name ?? $name,
            'type' => $type,
            'size' => $size,
            'nullable' => $nullable,
            'default' => $default,
            'after' => $after,
            'collation' => $collation
        ];
        return $this;
    }

    /**
     * Renames an existing column in a table.
     * @param string $name Column name to change.
     * @param string $new_name New column name.
     * @return Skeleton Current Skeleton instance for nested calls.
     */
    public function renameColumn(string $name, string $new_name)
    {
        $this->_modifiers[] = [
            'operation' => 'rename',
            'name' => $name,
            'new_name' => $new_name,
        ];
        return $this;
    }

    /**
     * Deletes an existing column from a table.
     * @param string $name Column name to drop.
     * @return Skeleton Current Skeleton instance for nested calls.
     */
    public function dropColumn(string $name)
    {
        $this->_modifiers[] = [
            'operation' => 'drop',
            'name' => $name
        ];
        return $this;
    }

    /**
     * Deletes timestamp fields from a table.
     * @param string $createdField (Optional) **Created at** field name.
     * @param string $updatedField (Optional) **Updated at** field name.
     * @return Skeleton Current Skeleton instance for nested calls.
     */
    public function dropTimestamps(string $createdField = 'created_at', string $updatedField = 'updated_at')
    {
        $this->dropColumn($createdField);
        $this->dropColumn($updatedField);
        return $this;
    }

    /**
     * Deletes soft deletes field from a table.
     * @param string $field (Optional) **Deleted at** field name.
     * @return Skeleton Current Skeleton instance for nested calls.
     */
    public function dropSoftDeletes(string $field = 'deleted_at')
    {
        return $this->dropColumn($field);
    }

    /**
     * Parse column modifiers into the query.
     * @param bool $isCreating (Optional) Indicates the creation of a table.
     */
    private function parseModifiers(bool $isCreating = false)
    {
        if (empty($this->_modifiers)) return;
        foreach ($this->_modifiers as $item) $this->modifyColumns($item, $isCreating);
    }

    /**
     * Sets the type of the last added/changed column.
     * @param string $type Column data type. Must be a valid type supported by your current database.
     * @return Skeleton Current Skeleton instance for nested calls.
     */
    public function type(string $type)
    {
        return $this->changeModifier('type', $type);
    }

    /**
     * Sets an UNSIGNED property to the type of the last added/changed column.
     * @return Skeleton Current Skeleton instance for nested calls.
     */
    public function unsigned()
    {
        return $this->changeModifier('unsigned', true);
    }

    /**
     * Sets the size of the last added/changed column.
     * @param mixed $size Field maximum length.
     * @return Skeleton Current Skeleton instance for nested calls.
     */
    public function size($size)
    {
        return $this->changeModifier('size', $size);
    }

    /**
     * Sets the default value of the last added/changed column.
     * @param mixed $value Default field value.
     * @return Skeleton Current Skeleton instance for nested calls.
     */
    public function default($value)
    {
        return $this->changeModifier('default', $value);
    }

    /**
     * Sets the default value of the last added/changed column to the current datetime.
     * @return Skeleton Current Skeleton instance for nested calls.
     */
    public function defaultNow()
    {
        return $this->default(self::raw('CURRENT_TIMESTAMP'));
    }

    /**
     * Sets the last added/changed column to be placed after an existing column.
     * @param string|null $column Name of other column to place this column after it.
     * @return Skeleton Current Skeleton instance for nested calls.
     */
    public function after(?string $column)
    {
        return $this->changeModifier('after', $column);
    }

    /**
     * Sets the collation of the last added/changed column.
     * @param string|null $collation Collation name to set.
     * @return Skeleton Current Skeleton instance for nested calls.
     */
    public function collation(?string $collation)
    {
        return $this->changeModifier('collation', $collation);
    }

    /**
     * Sets the charset of the last added/changed column.
     * @param string|null $charset Charset name to set.
     * @return Skeleton Current Skeleton instance for nested calls.
     */
    public function charset(?string $charset)
    {
        return $this->changeModifier('charset', $charset);
    }

    /**
     * Sets the last added/changed column to accept `NULL` values.
     * @return Skeleton Current Skeleton instance for nested calls.
     */
    public function nullable()
    {
        return $this->changeModifier('nullable', true);
    }

    /**
     * Sets the last added/changed column to not accept `NULL` values.
     * @return Skeleton Current Skeleton instance for nested calls.
     */
    public function notNull()
    {
        return $this->changeModifier('nullable', false);
    }

    /**
     * Renames the last changed column.
     * @param string|null $name New column name. Leave empty for keeping the current name.
     * @return Skeleton Current Skeleton instance for nested calls.
     */
    public function name(?string $name)
    {
        return $this->changeModifier('new_name', $name);
    }

    /**
     * Changes a column modifier property.
     * @param string $property Property name to change.
     * @param mixed $value Column value.
     * @return Skeleton Current Skeleton instance for nested calls.
     */
    private function changeModifier(string $property, $value)
    {
        if (empty($this->_modifiers)) throw new Exception('Skeleton: No column was added/changed to be modified');
        $i = count($this->_modifiers) - 1;
        if ($property === 'unsigned') {
            $this->_modifiers[$i]['type'] = $this->_modifiers[$i]['type'] . ' UNSIGNED';
        } else {
            $this->_modifiers[$i][$property] = $value;
        }
        return $this;
    }

    /**
     * Parse column operations.
     * @param array $data Associative array of data to parse.
     * @param bool $isCreating (Optional) Indicates the creation of a table.
     */
    private function modifyColumns(array $data, bool $isCreating = false)
    {
        // Database driver
        $driver = $this->getDriver();

        // Escape column name
        $data['name'] = $this->escapeIdentifier($data['name']);
        if (!empty($data['new_name'])) $data['new_name'] = $this->escapeIdentifier($data['new_name']);

        // Parse column name into query
        switch ($data['operation']) {
            case 'create':
            case 'add':
                $field = $isCreating ? "{$data['name']} " : "ADD COLUMN {$data['name']} ";
                break;
            case 'change':
                $field = "CHANGE COLUMN {$data['name']} {$data['new_name']} ";
                break;
            case 'modify':
                $field = "MODIFY COLUMN {$data['name']} ";
                break;
            case 'rename':
                $field = "RENAME COLUMN {$data['name']} TO {$data['new_name']} ";
                break;
            case 'drop':
                $field = "DROP COLUMN {$data['name']}";
                $this->_fields[] = $field;
                return;

            default:
                return;
        }

        // Rename or change column
        if ($data['operation'] !== 'rename') {
            // Field type and size
            $data['type'] = mb_strtoupper($data['type']);

            if (!is_null($data['size'])) {
                // Convert array to comma-separated values
                if (is_array($data['size'])) {
                    $data['size'] = "'" . implode("','", $data['size']) . "'";
                }

                if (Util::stringContains($data['type'], ' UNSIGNED')) {
                    $data['type'] = str_replace(' UNSIGNED', '', $data['type']);
                    $field .= $data['type'] . "({$data['size']}) UNSIGNED";
                } else {
                    $field .= $data['type'] . "({$data['size']})";
                }
            } else {
                $field .= $data['type'];
            }

            // Not nullable field
            if (!$data['nullable']) $field .= " NOT NULL";

            // Default value
            if ($data['default'] !== null) {
                if ($data['default'] instanceof stdClass) {
                    $field .= " DEFAULT {$data['default']->value}";
                } else {
                    $field .= " DEFAULT {$this->escape($data['default'])}";
                }
            }

            // Specific rules for MySQL driver only
            if ($driver === 'mysql') {
                // Charset
                if (!empty($data['charset'])) {
                    $field .= " CHARACTER SET '{$data['charset']}'";
                }

                // Collation
                if (!empty($data['collation'])) {
                    $field .= " COLLATE '{$data['collation']}'";
                }

                // After
                if ($data['operation'] !== 'create' && !empty($data['after'])) {
                    $data['after'] = $this->escapeIdentifier($data['after']);
                    $field .= " AFTER {$data['after']}";
                }
            }
        }

        // Saves the result
        $this->_fields[] = trim($field);
    }

    /**
     * Adds a table column to a PRIMARY KEY.
     * @param string|array $column A single column name or an array of columns to add to the primary key.
     * @return Skeleton Current Skeleton instance for nested calls.
     */
    public function primaryKey($column)
    {
        $column = is_array($column) ? $column : [$column];
        foreach ($column as $item) {
            $this->_primary[] = $this->escapeIdentifier($item);
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
    public function index($column, string $name = '', bool $unique = false)
    {
        $column = is_array($column) ? $column : [$column];
        foreach ($column as $item) {
            $key = $name;
            if (Util::isEmpty($name)) $key = $item;
            $item = $this->escapeIdentifier($item);
            $key = $this->escapeIdentifier($key);
            if ($unique) {
                $this->_unique[$key][] = $item;
            } else {
                $this->_index[$key][] = $item;
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
    public function unique($column, string $name = '')
    {
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
    public function foreignKey($column, string $table, $reference, ?string $name = null, string $update = 'RESTRICT', string $delete = 'RESTRICT')
    {
        $name = !Util::isEmpty($name) ? "CONSTRAINT {$this->escapeIdentifier($name)} " : '';
        $column = implode(', ', array_map([$this, 'escapeIdentifier'], is_array($column) ? $column : [$column]));
        $reference = implode(', ', array_map([$this, 'escapeIdentifier'], is_array($reference) ? $reference : [$reference]));
        $table = $this->escapeIdentifier($table);
        $this->_foreign[] = "{$name}FOREIGN KEY ({$column}) REFERENCES {$table} ({$reference}) ON UPDATE {$update} ON DELETE {$delete}";
        return $this;
    }

    /**
     * Sets the referential action on UPDATE to the last foreign key added to the table.
     * @param string $rule Valid options are: `CASCADE`, `SET NULL`, `RESTRICT`, `NO ACTION` or `SET DEFAULT`.
     * @return Skeleton Current Skeleton instance for nested calls.
     */
    public function onUpdate(string $rule)
    {
        if (empty($this->_foreign)) throw new Exception('Skeleton: No foreign key was added to be modified');
        $i = count($this->_foreign) - 1;
        $this->_foreign[$i] = preg_replace_callback('/ON UPDATE (.+) ON DELETE (.+)$/', function ($match) use ($rule) {
            return 'ON UPDATE ' . $rule . ' ON DELETE ' . $match[2];
        }, $this->_foreign[$i], 1);
        return $this;
    }

    /**
     * Sets the referential action on DELETE to the last foreign key added to the table.
     * @param string $rule Valid options are: `CASCADE`, `SET NULL`, `RESTRICT`, `NO ACTION` or `SET DEFAULT`.
     * @return Skeleton Current Skeleton instance for nested calls.
     */
    public function onDelete(string $rule)
    {
        if (empty($this->_foreign)) throw new Exception('Skeleton: No foreign key was added to be modified');
        $i = count($this->_foreign) - 1;
        $this->_foreign[$i] = preg_replace_callback('/ON UPDATE (.+) ON DELETE (.+)$/', function ($match) use ($rule) {
            return 'ON UPDATE ' . $match[1] . ' ON DELETE ' . $rule;
        }, $this->_foreign[$i], 1);
        return $this;
    }

    /**
     * Deletes an existing primary key from the table.
     * @return Skeleton Current Skeleton instance for nested calls.
     */
    public function dropPrimaryKey()
    {
        $this->_drops[] = "DROP PRIMARY KEY";
        return $this;
    }

    /**
     * Deletes an existing INDEX key from the table.
     * @param string $name The key name to drop.
     * @return Skeleton Current Skeleton instance for nested calls.
     */
    public function dropIndex(string $name)
    {
        $name = $this->escapeIdentifier($name);
        $this->_drops[] = "DROP INDEX {$name}";
        return $this;
    }

    /**
     * Deletes an existing UNIQUE INDEX key from the table.
     * @param string $name The key name to drop.
     * @return Skeleton Current Skeleton instance for nested calls.
     */
    public function dropUnique(string $name)
    {
        return $this->dropIndex($name);
    }

    /**
     * Deletes an existing FOREIGN KEY constraint from the table.
     * @param string $name The key name to drop.
     * @return Skeleton Current Skeleton instance for nested calls.
     */
    public function dropForeignKey(string $name)
    {
        $name = $this->escapeIdentifier($name);
        $this->_drops[] = "DROP FOREIGN KEY {$name}";
        return $this;
    }

    /**
     * Renames an existing INDEX key from the table.
     * @param string $name The key name.
     * @param string $new_name The new name to set.
     * @return Skeleton Current Skeleton instance for nested calls.
     */
    public function renameIndex(string $name, string $new_name)
    {
        $name = $this->escapeIdentifier($name);
        $new_name = $this->escapeIdentifier($new_name);
        $this->_drops[] = "RENAME INDEX {$name} TO {$new_name}";
        return $this;
    }

    /**
     * Adds an IF EXISTS statement to the query.
     * @return Skeleton Current Skeleton instance for nested calls.
     */
    public function ifExists()
    {
        $this->_exists = " IF EXISTS";
        return $this;
    }

    /**
     * Adds an IF NOT EXISTS statement to the query.
     * @return Skeleton Current Skeleton instance for nested calls.
     */
    public function ifNotExists()
    {
        $this->_exists = " IF NOT EXISTS";
        return $this;
    }

    /**
     * Adds a COLLATE setting to the table.
     * @param string $collate Collate to set.
     * @return Skeleton Current Skeleton instance for nested calls.
     */
    public function collate(string $collate)
    {
        $this->_collate = $collate;
        return $this;
    }

    /**
     * Adds a LIKE statement to the query.
     * @param string $table Table name to copy.
     * @return Skeleton Current Skeleton instance for nested calls.
     */
    public function like(string $table)
    {
        $this->_like = $table;
        return $this;
    }

    /**
     * Creates a new table.
     * @return bool Returns true on success.
     * @throws QueryException Throws an exception if the query fails.
     */
    public function create()
    {
        $this->_instruction = 'CREATE TABLE';
        $this->parseModifiers(true);
        return $this->execute();
    }

    /**
     * Creates a new temporary table for the current session.
     * @return bool Returns true on success.
     * @throws QueryException Throws an exception if the query fails.
     */
    public function createTemporary()
    {
        $this->_instruction = 'CREATE TEMPORARY TABLE';
        $this->parseModifiers(true);
        return $this->execute();
    }

    /**
     * Updates an existing table structure.
     * @return bool Returns true on success.
     * @throws QueryException Throws an exception if the query fails.
     */
    public function alter()
    {
        $this->_instruction = 'ALTER TABLE';
        $this->parseModifiers();
        return $this->execute();
    }

    /**
     * Changes the table name.
     * @param string $name New table name to rename to.
     * @return bool Returns true on success.
     * @throws QueryException Throws an exception if the query fails.
     */
    public function rename(string $name)
    {
        $this->_instruction = "RENAME TABLE";
        $this->_rename = $name;
        return $this->execute();
    }

    /**
     * Cleans the whole data from the table.
     * @return bool Returns true on success.
     * @throws QueryException Throws an exception if the query fails.
     */
    public function truncate()
    {
        $this->_instruction = "TRUNCATE TABLE";
        return $this->execute();
    }

    /**
     * Deletes the table from the database.
     * @return bool Returns true on success.
     * @throws QueryException Throws an exception if the query fails.
     */
    public function drop()
    {
        $this->_instruction = "DROP TABLE";
        return $this->execute();
    }

    /**
     * Creates a new database.
     * @param string $name Database name to create.
     * @return bool Returns true on success.
     * @throws QueryException Throws an exception if the query fails.
     */
    public function createDatabase(string $name)
    {
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
    public function dropDatabase(string $name)
    {
        $this->_instruction = 'DROP DATABASE';
        $this->_database = $name;
        return $this->execute();
    }

    /**
     * Checks if a column exists in the current table.
     * @param string $column Column name to check.
     * @return bool Returns true if the column exists, false otherwise.
     */
    public function columnExists(string $column)
    {
        // Builds the query to the database driver
        $driver = $this->getDriver();
        $column = $this->escape($column);
        switch ($driver) {
            case 'sqlite':
                $this->_raw = "PRAGMA table_info({$this->_table})";
                break;
            case 'pgsql':
                $this->_raw = "SELECT column_name FROM information_schema.columns WHERE table_name = '{$this->_table}' AND column_name = {$column}";
                break;
            case 'sqlsrv':
                $this->_raw = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '{$this->_table}' AND COLUMN_NAME = {$column}";
                break;
            default:
                $table = $this->escapeIdentifier($this->_table);
                $this->_raw = "SHOW COLUMNS FROM {$table} LIKE {$column}";
                break;
        }

        // Returns the query result
        $result = $this->execute(true, false);
        if ($driver === 'sqlite') $result = array_filter($result, fn($col) => $col['name'] === $column);
        return !empty($result);
    }

    /**
     * Checks if a table exists in the current database.
     * @param string|null $table (Optional) Table name to check, leave empty to use the current working table.
     * @return bool Returns true if the table exists, false otherwise.
     */
    public function tableExists(?string $table = null)
    {
        // Checks if the table name is empty
        if (Util::isEmpty($table)) $table = $this->_table;

        // Builds the query to the database driver
        switch ($this->getDriver()) {
            case 'sqlite':
                $this->_raw = "SELECT name FROM sqlite_master WHERE type='table' AND name='{$table}'";
                break;
            case 'pgsql':
                $this->_raw = "SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname='public' AND tablename='{$table}'";
                break;
            case 'sqlsrv':
                $this->_raw = "SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = '{$table}'";
                break;
            default:
                $this->_raw = "SHOW TABLES LIKE '{$table}'";
                break;
        }

        // Returns the query result
        $result = $this->execute(true, false);
        return !empty($result);
    }

    /**
     * Clears the current built query entirely.
     */
    public function clearQuery()
    {
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
        $this->_modifiers = [];
        $this->_like = '';
        $this->_database = '';
        $this->_raw = '';
        $this->_prepared = [];
        $this->_postCreate = [];
        return $this;
    }

    /**
     * Returns the current built query.
     * @return string Current built query.
     */
    public function getQuery()
    {
        // Checks for raw query
        if (!Util::isEmpty($this->_raw)) return $this->_raw;

        // Gets the instruction and database driver
        $driver = $this->getDriver();
        $query = $this->_instruction;

        // Gets EXISTS
        if (in_array($this->_instruction, ['CREATE TABLE', 'CREATE TEMPORARY TABLE', 'TRUNCATE TABLE', 'DROP TABLE', 'CREATE DATABASE', 'DROP DATABASE'])) {
            if (!Util::isEmpty($this->_exists)) $query .= $this->_exists;
        }

        // Gets DATABASE statements
        if ($this->_instruction === 'CREATE DATABASE' || $this->_instruction === 'DROP DATABASE') {
            $db = $this->escapeIdentifier($this->_database);
            $query .= " {$db}";
            return $query;
        }

        // Gets the table
        $table = $this->escapeIdentifier($this->_table);
        $query .= " {$table}";

        // Gets CREATE TABLE parameters
        if ($this->_instruction === 'CREATE TABLE' || $this->_instruction === 'CREATE TEMPORARY TABLE') {
            // Opening parenthesis
            $instructions = [];
            $query .= ' (';

            // Like
            if (!Util::isEmpty($this->_like)) {
                $like = $this->escapeIdentifier($this->_like);
                $instructions[] = "LIKE {$like}";
            }

            // Auto increment
            if (!Util::isEmpty($this->_autoincrement)) $instructions[] = $this->_autoincrement;

            // Fields
            if (!empty($this->_fields)) $instructions = array_merge($instructions, $this->_fields);

            // Primary keys
            if (!empty($this->_primary)) {
                $primary = implode(', ', $this->_primary);
                $instructions[] = "PRIMARY KEY ({$primary})";
            }

            // Unique indexes
            if (!empty($this->_unique)) {
                if ($driver === 'sqlite') {
                    foreach ($this->_unique as $name => $unique) {
                        $fields = implode(', ', $unique);
                        $this->_postCreate[] = "CREATE UNIQUE INDEX {$name} ON {$table} ({$fields})";
                    }
                } else {
                    foreach ($this->_unique as $name => $unique) {
                        $fields = implode(', ', $unique);
                        $instructions[] = "UNIQUE INDEX {$name} ({$fields})";
                    }
                }
            }

            // Indexes
            if (!empty($this->_index)) {
                if ($driver === 'sqlite') {
                    foreach ($this->_index as $name => $key) {
                        $fields = implode(', ', $key);
                        $this->_postCreate[] = "CREATE INDEX {$name} ON {$table} ({$fields})";
                    }
                } else {
                    foreach ($this->_index as $name => $key) {
                        $fields = implode(', ', $key);
                        $instructions[] = "INDEX {$name} ({$fields})";
                    }
                }
            }

            // Foreign keys
            if (!empty($this->_foreign)) $instructions = array_merge($instructions, $this->_foreign);

            // Creates the instruction
            $query .= implode(', ', $instructions);

            // Closing parenthesis
            $query .= ')';

            // Collate
            if ($driver === 'mysql' && !Util::isEmpty($this->_collate)) {
                $query .= " COLLATE=\"{$this->_collate}\"";
            }
        }

        // Gets ALTER TABLE parameters
        if ($this->_instruction === 'ALTER TABLE') {
            $instructions = [];
            $query .= ' ';

            // Auto increment
            if (!Util::isEmpty($this->_autoincrement)) $instructions[] = "ADD COLUMN {$this->_autoincrement}";

            // Fields
            if (!empty($this->_fields)) $instructions = array_merge($instructions, $this->_fields);

            // Key drops
            if (!empty($this->_drops)) $instructions = array_merge($instructions, $this->_drops);

            // Primary keys
            if (!empty($this->_primary)) {
                $primary = implode(', ', $this->_primary);
                $instructions[] = "ADD PRIMARY KEY ({$primary})";
            }

            // Unique indexes
            if (!empty($this->_unique)) {
                foreach ($this->_unique as $name => $unique) {
                    $fields = implode(', ', $unique);
                    $instructions[] = "ADD UNIQUE INDEX {$name} ({$fields})";
                }
            }

            // Indexes
            if (!empty($this->_index)) {
                foreach ($this->_index as $name => $key) {
                    $fields = implode(', ', $key);
                    $instructions[] = "ADD INDEX {$name} ({$fields})";
                }
            }

            // Foreign keys
            if (!empty($this->_foreign)) {
                foreach ($this->_foreign as $foreign) {
                    $instructions[] = "ADD {$foreign}";
                }
            }

            // Collate
            if ($driver === 'mysql' && !Util::isEmpty($this->_collate)) {
                $instructions[] = "COLLATE=\"{$this->_collate}\"";
            }

            // Creates the instruction
            $query .= implode(', ', $instructions);
        }

        // Gets RENAME TABLE parameters
        if ($this->_instruction === 'RENAME TABLE') {
            $rename = $this->escapeIdentifier($this->_rename);
            $query .= " TO {$rename}";
            $this->table($this->_rename);
        }

        // Returns the result
        return $query;
    }
}
