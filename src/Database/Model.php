<?php

namespace Glowie\Core\Database;

use BadMethodCallException;
use Glowie\Core\Element;
use Glowie\Core\Traits\ElementTrait;
use Glowie\Core\Collection;
use Util;
use Exception;
use JsonSerializable;
use DateTime;

/**
 * Model core for Glowie application.
 * @category Model
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 * @see https://glowie.gabrielsilva.dev.br/docs/latest/forms-and-data/models
 */
class Model extends Kraken implements JsonSerializable
{
    use ElementTrait;

    /**
     * Model table name. Leave empty for auto.
     * @var string
     */
    protected $_table = '';

    /**
     * Model database connection name (from your app configuration).
     * @var string
     */
    protected $_database = 'default';

    /**
     * Table primary key name.
     * @var string
     */
    protected $_primaryKey = 'id';

    /**
     * Enable the use of UUIDs in the table.
     * @var bool
     */
    protected $_uuid = false;

    /**
     * Table retrievable fields.
     * @var array
     */
    protected $_fields = [];

    /**
     * Table updatable fields.
     * @var array
     */
    protected $_updatable = [];

    /**
     * Initial model attributes.
     * @var array
     */
    protected $_attributes = [];

    /**
     * Table fields data types to cast.
     * @var array
     */
    protected $_casts = [];

    /**
     * Table fields data types to mutate.
     * @var array
     */
    protected $_mutators = [];

    /**
     * Handle timestamp fields.
     * @var bool
     */
    protected $_timestamps = false;

    /**
     * Timestamp fields date format.
     * @var string
     */
    protected $_dateFormat = 'Y-m-d H:i:s';

    /**
     * Use soft deletes in the table.
     * @var bool
     */
    protected $_softDeletes = false;

    /**
     * **Created at** field name (if timestamps enabled).
     * @var string
     */
    protected $_createdField = 'created_at';

    /**
     * **Updated at** field name (if timestamps enabled).
     * @var string
     */
    protected $_updatedField = 'updated_at';

    /**
     * **Deleted at** field name (if soft deletes enabled).
     * @var string
     */
    protected $_deletedField = 'deleted_at';

    /**
     * The initial data from a filled row.
     * @var Element|null
     */
    private $_initialData = null;

    /**
     * If casting data is enabled.
     * @var bool
     */
    private $_castingEnabled = true;

    /**
     * If mutating data is enabled.
     * @var bool
     */
    private $_mutateEnabled = true;

    /**
     * Model table relations.
     * @var array
     */
    private $_relations = [];

    /**
     * Array of enabled relations for the next queries.
     * @var bool|array
     */
    private $_relationsEnabled = false;

    /**
     * Creates a new instance of the model.
     * @param Element|array $data An Element or associative array with the initial data to fill the model entity.\
     * This data will be merged into the initial model attributes, if filled.
     * @param bool $init (Optional) Initialize the model relationships.
     */
    public function __construct($data = [], bool $init = true)
    {
        // Gets the table name
        if (Util::isEmpty($this->_table)) $this->_table = Util::snakeCase(Util::pluralize(Util::classname($this)));

        // Constructs the query builder
        Kraken::__construct($this->_table, $this->_database);

        // Sets the initial data
        if ($data instanceof Element || $data instanceof Collection) $data = $data->toArray();
        $data = array_merge($this->_attributes, $data);
        if (!empty($data)) $this->fill($data);

        // Initialize model
        if ($init && method_exists($this, 'init')) $this->init();
    }

    /**
     * Creates a new instance of the model in a static-binding.
     * @param Element|array $data An Element or associative array with the initial data to fill the model entity.\
     * This data will be merged into the initial model attributes, if filled.
     * @param bool $init (Optional) Initialize the model relationships.
     * @return $this New instance of the model.
     */
    public static function make($data = [], bool $init = true)
    {
        return new static($data, $init);
    }

    /**
     * Calls a magic method.
     * @param string $name Method name.
     * @param array $args Method arguments.
     * @return $this Current instance for nested calls.
     */
    public function __call(string $name, array $args)
    {
        // Magic where()
        if (Util::startsWith($name, 'where')) {
            $field = Util::snakeCase(Util::replaceFirst($name, 'where', ''));
            return $this->where($field, $args[0] ?? null, $args[1] ?? null, $args[2] ?? 'AND');

            // Magic orWhere()
        } else if (Util::startsWith($name, 'orWhere')) {
            $field = Util::snakeCase(Util::replaceFirst($name, 'orWhere', ''));
            return $this->orWhere($field, $args[0] ?? null, $args[1] ?? null);

            // Magic findBy()
        } else if (Util::startsWith($name, 'findBy')) {
            $field = Util::snakeCase(Util::replaceFirst($name, 'findBy', ''));
            return $this->findBy($field, $args[0] ?? null, $args[1] ?? false);

            // Magic findAndFillBy()
        } else if (Util::startsWith($name, 'findAndFillBy')) {
            $field = Util::snakeCase(Util::replaceFirst($name, 'findAndFillBy', ''));
            return $this->findAndFillBy($field, $args[0] ?? null, $args[1] ?? false, $args[2] ?? false);

            // Magic allBy()
        } else if (Util::startsWith($name, 'allBy')) {
            $field = Util::snakeCase(Util::replaceFirst($name, 'allBy', ''));
            return $this->allBy($field, $args[0] ?? null, $args[1] ?? false);

            // Magic dropBy()
        } else if (Util::startsWith($name, 'dropBy')) {
            $field = Util::snakeCase(Util::replaceFirst($name, 'allBy', ''));
            return $this->dropBy($field, $args[0] ?? null, $args[1] ?? false);

            // Method not found
        } else {
            throw new BadMethodCallException(sprintf('Call to undefined method %s::%s()', get_class($this), $name));
        }
    }

    /**
     * Fetches the first result from a SELECT query. Results will be casted, if available.
     * @return mixed Returns the first resulting row on success or null if not found.
     * @throws QueryException Throws an exception if the query fails.
     */
    public function fetchRow()
    {
        return $this->attachRelations($this->castData(Kraken::fetchRow()));
    }

    /**
     * Fetches all results from a SELECT query. Results will be casted, if available.
     * @return Collection Returns a Collection with all resulting rows.
     * @throws QueryException Throws an exception if the query fails.
     */
    public function fetchAll()
    {
        return $this->attachRelations($this->castData(Kraken::fetchAll()));
    }

    /**
     * Fetches all results from a SELECT query with pagination.
     * @param int $currentPage (Optional) Current page to get results.
     * @param int $resultsPerPage (Optional) Number of results to get per page.
     * @param int|null $range (Optional) Pagination range interval (for `pages` array).
     * @param bool $deleted (Optional) Include deleted rows (if soft deletes enabled).
     * @return Element Returns an Element with the pagination result.
     * @throws QueryException Throws an exception if the query fails.
     */
    public function paginate(int $currentPage = 1, int $resultsPerPage = 25, ?int $range = null, bool $deleted = false)
    {
        if ($this->_softDeletes && !$deleted) $this->whereNull($this->_table . '.' . $this->_deletedField);
        return Kraken::paginate($currentPage, $resultsPerPage, $range);
    }

    /**
     * Gets the first row that matches the model primary key value.
     * @param mixed $primary (Optional) Primary key value to search for.
     * @param bool $deleted (Optional) Include deleted rows (if soft deletes enabled).
     * @return mixed Returns the row on success or null if not found.
     */
    public function find($primary = null, bool $deleted = false)
    {
        $fields = !Util::isEmpty($this->_select) ? $this->_select : (!Util::isEmpty($this->_fields) ? $this->_fields : '*');
        if (!is_null($primary)) $this->where($this->_table . '.' . $this->_primaryKey, $primary);
        if ($this->_softDeletes && !$deleted) $this->whereNull($this->_table . '.' . $this->_deletedField);
        return $this->select($fields)->fetchRow();
    }

    /**
     * Gets the first row that matches a field value.
     * @param string|array $field Field name to use while searching or an associative array relating fields and values to search.
     * @param mixed $value (Optional) Value to search for.
     * @param bool $deleted (Optional) Include deleted rows (if soft deletes enabled).
     * @return mixed Returns the row on success or null if not found.
     */
    public function findBy($field, $value = null, bool $deleted = false)
    {
        $this->filterFields($field, $value);
        return $this->find(null, $deleted);
    }

    /**
     * Gets the first row that matches the model primary key value, then fills the model entity with the row data if found.
     * @param mixed $primary (Optional) Primary key value to search for.
     * @param bool $deleted (Optional) Include deleted rows (if soft deletes enabled).
     * @param bool $overwrite (Optional) Set to `true` to overwrite the existing model data instead of merging.
     * @return mixed Returns the current Model instance if the row is found, false otherwise.
     */
    public function findAndFill($primary = null, bool $deleted = false, bool $overwrite = false)
    {
        $result = $this->find($primary, $deleted);
        if (!$result) return false;
        return $this->fill($result, $overwrite);
    }

    /**
     * Gets the first row that matches a field value, then fills the model entity with the row data if found.
     * @param string|array $field Field name to use while searching or an associative array relating fields and values to search.
     * @param mixed $value (Optional) Value to search for.
     * @param bool $deleted (Optional) Include deleted rows (if soft deletes enabled).
     * @param bool $overwrite (Optional) Set to `true` to overwrite the existing model data instead of merging.
     * @return mixed Returns the current Model instance if the row is found, false otherwise.
     */
    public function findAndFillBy($field, $value = null, bool $deleted = false, bool $overwrite = false)
    {
        $result = $this->findBy($field, $value, $deleted);
        if (!$result) return false;
        return $this->fill($result, $overwrite);
    }

    /**
     * Gets all rows from the model table.
     * @param bool $deleted (Optional) Include deleted rows (if soft deletes enabled).
     * @return Collection Returns a Collection with all rows.
     */
    public function all(bool $deleted = false)
    {
        $fields = !Util::isEmpty($this->_select) ? $this->_select : (!Util::isEmpty($this->_fields) ? $this->_fields : '*');
        if ($this->_softDeletes && !$deleted) $this->whereNull($this->_table . '.' . $this->_deletedField);
        return $this->select($fields)->fetchAll();
    }

    /**
     * Gets filtered rows matching a field value.
     * @param string|array $field Field name to use while searching or an associative array relating fields and values to search.
     * @param mixed $value (Optional) Value to search for.
     * @param bool $deleted (Optional) Include deleted rows (if soft deletes enabled).
     * @return Collection Returns a Collection with the filtered rows.
     */
    public function allBy($field, $value = null, bool $deleted = false)
    {
        $this->filterFields($field, $value);
        return $this->all($deleted);
    }

    /**
     * Gets all rows from the model table ordering by the newest **created at** field.
     * @param bool $deleted (Optional) Include deleted rows (if soft deletes enabled).
     * @return Collection Returns a Collection with all rows.
     * @throws Exception Throws an exception if the model is not handling timestamp fields.
     */
    public function latest(bool $deleted = false)
    {
        if (!$this->_timestamps) throw new Exception('latest(): Model "' . get_class($this) . '" is not handling timestamp fields');
        return $this->orderByDesc($this->_table . '.' . $this->_createdField)->all($deleted);
    }

    /**
     * Gets all rows from the model table ordering by the oldest **created at** field.
     * @param bool $deleted (Optional) Include deleted rows (if soft deletes enabled).
     * @return Collection Returns a Collection with all rows.
     * @throws Exception Throws an exception if the model is not handling timestamp fields.
     */
    public function oldest(bool $deleted = false)
    {
        if (!$this->_timestamps) throw new Exception('oldest(): Model "' . get_class($this) . '" is not handling timestamp fields');
        return $this->orderBy($this->_table . '.' . $this->_createdField)->all($deleted);
    }

    /**
     * Deletes the first row that matches the model primary key value.
     * @param mixed $primary (Optional) Primary key value to search for. You can also use an array of values.
     * @param bool $force (Optional) Bypass soft deletes (if enabled) and permanently delete the row.
     * @return bool Returns true on success or false on failure.
     */
    public function drop($primary = null, bool $force = false)
    {
        if (!is_null($primary)) $this->whereIn($this->_table . '.' . $this->_primaryKey, (array)$primary);
        if ($this->_softDeletes && !$force) {
            return $this->update([$this->_table . '.' . $this->_deletedField => self::raw('CURRENT_TIMESTAMP')]);
        } else {
            return $this->delete($this->_table);
        }
    }

    /**
     * Deletes rows matching a field value.
     * @param string|array $field Field name to use while searching or an associative array relating fields and values to search.
     * @param mixed $value (Optional) Value to search for.
     * @param bool $force (Optional) Bypass soft deletes (if enabled) and permanently delete the rows.
     * @return bool Returns true on success or false on failure.
     */
    public function dropBy($field, $value = null, bool $force = false)
    {
        $this->filterFields($field, $value);
        if ($this->_softDeletes && !$force) {
            return $this->update([$this->_table . '.' . $this->_deletedField => self::raw('CURRENT_TIMESTAMP')]);
        } else {
            return $this->delete($this->_table);
        }
    }

    /**
     * Permanently removes soft deleted rows (if enabled).
     * @return bool Returns true on success or false on failure.
     * @throws Exception Throws an exception if the model soft deletes are not enabled.
     */
    public function purge()
    {
        if (!$this->_softDeletes) throw new Exception('purge(): Model "' . get_class($this) . '" soft deletes are not enabled');
        $this->clearQuery();
        $this->whereNotNull($this->_table . '.' . $this->_deletedField);
        return $this->delete();
    }

    /**
     * Gets only a list of soft deleted rows (if enabled).
     * @return Collection Returns a Collection with the filtered rows.
     */
    public function getDeleted()
    {
        if (!$this->_softDeletes) throw new Exception('getDeleted(): Model "' . get_class($this) . '" soft deletes are not enabled');
        $this->whereNotNull($this->_table . '.' . $this->_deletedField);
        return $this->all(true);
    }

    /**
     * Inserts a new row in the model table.
     * @param mixed $data An Element or associative array/Collection relating fields and values to insert.
     * @return mixed Returns the last inserted `AUTO_INCREMENT` value (or true) on success, false on failure.\
     * If the model uses UUIDs, upon success the last generated UUID will be returned.
     */
    public function create($data)
    {
        // Clears the current built query
        $this->clearQuery();

        // Parse data and timestamps
        if ($data instanceof Element || $data instanceof Collection) $data = $data->toArray();
        $data = $this->mutateData($this->filterData($data));
        if ($this->_timestamps) {
            $data[$this->_createdField] = date($this->_dateFormat);
            $data[$this->_updatedField] = date($this->_dateFormat);
        }

        // Generate UUID if in use
        if ($this->_uuid) $data[$this->_primaryKey] = $data[$this->_primaryKey] ?? Util::orderedUuid();

        // Inserts the element
        $result = $this->insert($data);

        // Return result
        if ($result === false) return $result;
        if (isset($data[$this->_primaryKey])) return $data[$this->_primaryKey];
        if ($this->lastInsertId()) return $this->lastInsertId();
        return $result;
    }

    /**
     * Gets the first row that matches a set of fields and values. If no matching row is found, a new one is created and returned.
     * @param Element|array $find An Element or associative array of fields and values to search.
     * @param Element|array $create (Optional) An Element or associative array of data to merge into the `$find` fields to create a new row.
     * @param bool $deleted (Optional) Include deleted rows (if soft deletes enabled).
     * @return mixed Returns the existing or new row, false on error.
     */
    public function findOrCreate($find, $create = [], bool $deleted = false)
    {
        // Convert data to arrays
        if ($find instanceof Element || $find instanceof Collection) $find = $find->toArray();
        if ($create instanceof Element || $create instanceof Collection) $create = $create->toArray();

        // Tries to find the existing row
        $row = $this->findBy($find, null, $deleted);
        if ($row) return $row;

        // Row does not exist, create it
        $id = $this->create(array_merge($find, $create));
        if (is_bool($id)) return $id;
        return $this->find($id);
    }

    /**
     * Checks if a row matches the primary key value in the data. If so, updates the row. Otherwise,\
     * inserts a new record in the model table.
     * @param Element|array $data An Element or associative array relating fields and values to upsert. **Must include the primary key field to update.**
     * @return mixed Returns the last inserted `AUTO_INCREMENT` value (or true) if the row is created, otherwise returns true on success or false on failure.
     */
    public function updateOrCreate($data)
    {
        // Clears the current built query
        $this->clearQuery();

        // Checks if the primary key was passed and matches an existing row
        if ($data instanceof Element || $data instanceof Collection) $data = $data->toArray();
        if (isset($data[$this->_primaryKey]) && $this->find($data[$this->_primaryKey])) {
            return $this->where($this->_primaryKey, $data[$this->_primaryKey])->update($data);
        } else {
            return $this->create($data);
        }
    }

    /**
     * Checks if a row matches a set of fields and values. If so, updates the row. Otherwise,\
     * inserts a new record in the model table.
     * @param Element|array $find An Element or associative array of fields and values to search.
     * @param Element|array $data (Optional) An Element or associative array of data to merge into the `$find` fields to update/create a new row.
     * @return mixed Returns the last inserted `AUTO_INCREMENT` value (or true) if the row is created, otherwise returns true on success or false on failure.
     */
    public function updateOrCreateBy($find, $data = [])
    {
        // Convert data to arrays
        if ($find instanceof Element || $find instanceof Collection) $find = $find->toArray();
        if ($data instanceof Element || $data instanceof Collection) $data = $data->toArray();

        // Checks if row exists
        $row = $this->findBy($find);

        // If row exists, update or create it
        if ($row) {
            $row = $row->toArray();
            return $this->where($this->_primaryKey, $row[$this->_primaryKey])->update($data);
        } else {
            return $this->create(array_merge($find, $data));
        }
    }

    /**
     * Updates data in the model table.\
     * **Do not forget to use WHERE statements before calling this function, otherwise all records will be updated.**
     * @param mixed $data An associative array/Collection/Element relating fields and values to update.
     * @return bool Returns true on success or false on failure.
     * @throws QueryException Throws an exception if the query fails.
     */
    public function update($data)
    {
        if ($data instanceof Element || $data instanceof Collection) $data = $data->toArray();
        $data = $this->mutateData($this->filterData($data));
        if ($this->_timestamps) $data[$this->_updatedField] = date($this->_dateFormat);
        return Kraken::update($data);
    }

    /**
     * Counts the number of resulting rows from a SELECT query.
     * @param string $column (Optional) Column to use as the counting base. Using `*` will count all rows including NULL values.\
     * Setting a column name will count all rows excluding NULL values from that column. You can also use a raw COUNT expression.
     * @param bool $deleted (Optional) Include deleted rows (if soft deletes enabled).
     * @return int Returns the number of rows on success.
     * @throws QueryException Throws an exception if the query fails.
     */
    public function count(string $column = '*', bool $deleted = false)
    {
        if ($this->_softDeletes && !$deleted) $this->whereNull($this->_table . '.' . $this->_deletedField);
        return Kraken::count($column);
    }

    /**
     * Sums the value of all rows in a specific column.
     * @param string $column Column to retrieve values. You can also use a raw SUM expression.
     * @param bool $deleted (Optional) Include deleted rows (if soft deletes enabled).
     * @return string Returns the sum result on success.
     * @throws QueryException Throws an exception if the query fails.
     */
    public function sum(string $column, bool $deleted = false)
    {
        if ($this->_softDeletes && !$deleted) $this->whereNull($this->_table . '.' . $this->_deletedField);
        return Kraken::sum($column);
    }

    /**
     * Returns the highest value from a specific column.
     * @param string $column Column to retrieve the value. You can also use a raw MAX expression.
     * @param bool $deleted (Optional) Include deleted rows (if soft deletes enabled).
     * @return string Returns the highest value on success.
     * @throws QueryException Throws an exception if the query fails.
     */
    public function max(string $column, bool $deleted = false)
    {
        if ($this->_softDeletes && !$deleted) $this->whereNull($this->_table . '.' . $this->_deletedField);
        return Kraken::max($column);
    }

    /**
     * Returns the lowest value from a specific column.
     * @param string $column Column to retrieve the value. You can also use a raw MIN expression.
     * @param bool $deleted (Optional) Include deleted rows (if soft deletes enabled).
     * @return string Returns the lowest value on success.
     * @throws QueryException Throws an exception if the query fails.
     */
    public function min(string $column, bool $deleted = false)
    {
        if ($this->_softDeletes && !$deleted) $this->whereNull($this->_table . '.' . $this->_deletedField);
        return Kraken::min($column);
    }

    /**
     * Returns the average value from a specific column.
     * @param string $column Column to retrieve the value. You can also use a raw AVG expression.
     * @param bool $deleted (Optional) Include deleted rows (if soft deletes enabled).
     * @return string Returns the average value on success.
     * @throws QueryException Throws an exception if the query fails.
     */
    public function avg(string $column, bool $deleted = false)
    {
        if ($this->_softDeletes && !$deleted) $this->whereNull($this->_table . '.' . $this->_deletedField);
        return Kraken::avg($column);
    }

    /**
     * Checks if there are any records that match a SELECT query.
     * @param bool $deleted (Optional) Include deleted rows (if soft deletes enabled).
     * @return bool Returns true if exists or false if not.
     * @throws QueryException Throws an exception if the query fails.
     */
    public function exists(bool $deleted = false)
    {
        if ($this->_softDeletes && !$deleted) $this->whereNull($this->_table . '.' . $this->_deletedField);
        $result = $this->count();
        return (is_int($result) && $result >= 1);
    }

    /**
     * Checks if there are not any records that match a SELECT query.
     * @param bool $deleted (Optional) Include deleted rows (if soft deletes enabled).
     * @return bool Returns true if does not exist or false if it does.
     * @throws QueryException Throws an exception if the query fails.
     */
    public function doesntExist(bool $deleted = false)
    {
        return !$this->exists($deleted);
    }

    /**
     * Fills the model entity with a row data. This data will be merged into the existing model data, if any.
     * @param Element|array $row An Element or associative array with the row data to fill.
     * @param bool $overwrite (Optional) Set to `true` to overwrite the existing model data instead of merging.
     * @return $this Current Model instance for nested calls.
     */
    public function fill($row, bool $overwrite = false)
    {
        if ($row instanceof Element || $row instanceof Collection) $row = $row->toArray();
        if (!$overwrite) $row = array_merge($this->toArray(), $row);
        $this->_initialData = new Element($row);
        $this->__constructTrait($row);
        return $this;
    }

    /**
     * Checks if the initial row data has been modified in the model entity.
     * @param string $field (Optional) Field to check. Leave empty to compare everything.
     * @return bool Returns true if the row data has been modified or false otherwise.
     * @throws Exception Throws an exception if the model entity is not filled with a row data.
     */
    public function isDirty(string $field = '')
    {
        if (!$this->_initialData) throw new Exception('isDirty(): Model "' . get_class($this) . '" entity was not filled with a row data');
        if (!Util::isEmpty($field)) {
            return ($this->_initialData->get($field) !== $this->get($field));
        } else {
            return ($this->_initialData->toArray() !== $this->toArray());
        }
    }

    /**
     * Checks if the initial row data has not been modified in the model entity.
     * @param string $field (Optional) Field to check. Leave empty to compare everything.
     * @return bool Returns true if the row data has not been modified or false otherwise.
     * @throws Exception Throws an exception if the model entity is not filled with a row data.
     */
    public function isPristine(string $field = '')
    {
        if (!$this->_initialData) throw new Exception('isPristine(): Model "' . get_class($this) . '" entity was not filled with a row data');
        return !$this->isDirty($field);
    }

    /**
     * Resets the model entity back to the original filled data.\
     * **Note:** this will delete all modifications made to the model entity data.
     * @return $this Current Model instance for nested calls.
     */
    public function reset()
    {
        if (!$this->_initialData) throw new Exception('reset(): Model "' . get_class($this) . '" entity was not filled with a row data');
        return $this->fill($this->_initialData, true);
    }

    /**
     * Refreshes and refills the model entity data back from the database using its primary key.\
     * **Note:** this will delete all modifications made to the model entity data.
     * @param bool $deleted (Optional) Include deleted rows (if soft deletes enabled).
     * @return mixed Returns the current Model instance if the row is found, false otherwise.
     */
    public function refresh(bool $deleted = false)
    {
        // Checks if filled model entity
        if (!$this->_initialData) throw new Exception('refresh(): Model "' . get_class($this) . '" entity was not filled with a row data');

        // Get primary key value
        $primary = $this->_initialData->get($this->_primaryKey);
        if (is_null($primary)) throw new Exception('refresh(): Model "' . get_class($this) . '" entity primary key was not filled');

        // Refills the model
        return $this->findAndFill($primary, $deleted, true);
    }

    /**
     * Saves the model entity data to the database.
     * @return bool Returns the last inserted `AUTO_INCREMENT` value (or true) if the row is created, otherwise returns true on success or false on failure.
     */
    public function save()
    {
        $data = $this->toArray();
        if (empty($data)) throw new Exception('save(): Model "' . get_class($this) . '" entity cannot be empty while saving');
        $result = $this->updateOrCreate($data);

        // Parse primary key value
        if (is_bool($result)) return $result;
        $this->{$this->_primaryKey} = $result;
        return $result;
    }

    /**
     * Deletes the database row matching the model entity primary key value.
     * @return bool Returns true on success or false on failure.
     * @throws Exception Throws an exception if the model entity primary key is not filled.
     */
    public function destroy()
    {
        $primary = $this->getPrimary();
        if (is_null($primary)) throw new Exception('destroy(): Model "' . get_class($this) . '" entity primary key was not filled');
        return $this->drop($primary);
    }

    /**
     * Clones the current model entity removing the primary key and timestamp fields.
     * @param array $excluded (Optional) Array of aditional fields to delete from the model.
     * @return $this Returns a copy as a new instance of the current model.
     */
    public function clone(array $fields = [])
    {
        $model = clone $this;
        $model->remove([$this->_primaryKey, $this->_createdField, $this->_updatedField, $this->_deletedField, ...$fields]);
        return $model;
    }

    /**
     * Enables relating data with other models.
     * @param string|array $names (Optional) Relation name or an array of relations to enable. Leave empty to use all.
     * @return $this Current Model instance for nested calls.
     */
    public function withRelations($names = [])
    {
        $this->_relationsEnabled = (array)$names;
        return $this;
    }

    /**
     * Disables relating data with other models.
     * @return $this Current Model instance for nested calls.
     */
    public function withoutRelations()
    {
        $this->_relationsEnabled = false;
        return $this;
    }

    /**
     * Disabled soft deletes setting in the model.
     * @return $this Current Model instance for nested calls.
     */
    public function withDeleted()
    {
        $this->_softDeletes = false;
        return $this;
    }

    /**
     * Enables soft deletes settings in the model.
     * @return $this Current Model instance for nested calls.
     */
    public function withoutDeleted()
    {
        $this->_softDeletes = true;
        return $this;
    }

    /**
     * Sets a condition to the last relation created in the Model.
     * @param callable $callback A function that receives the related model instance and joining value as references.
     * @return $this Current Model instance for nested calls.
     */
    public function relationsWhen(callable $callback)
    {
        if (empty($this->_relations)) throw new Exception('Model: No relation created to be modified');
        $i = array_key_last($this->_relations);
        $this->_relations[$i]['callback'] = $callback;
        return $this;
    }

    /**
     * Setup a one to one relationship with another model.
     * @param string $model Related model classname with namespace. You can use `ModelName::class` to get this property correctly.
     * @param string $column (Optional) Foreign key column name of the current model in the related table. Leave empty for auto.
     * @param string $name (Optional) Name of this relation to add to the query results. Leave empty for auto.
     * @param callable|null $callback (Optional) A function to interact with the related model before querying the relationship.\
     * It receives the related Model instance as the first parameter, and the current row as an associative array.
     * @return $this Current Model instance for nested calls.
     */
    public function hasOne(string $model, string $column = '', string $name = '', ?callable $callback = null)
    {
        // Get primary key and names
        $primary = $this->getPrimaryName();
        if (Util::isEmpty($name)) $name = Util::snakeCase(Util::singularize(Util::classname($model)));
        if (Util::isEmpty($column)) $column = Util::snakeCase(Util::singularize(Util::classname($this))) . '_' . $primary;

        // Set to relations array
        $this->_relations[$name] = [
            'type' => 'one',
            'model' => $model,
            'primary' => $primary,
            'column' => $column,
            'callback' => $callback
        ];

        // Return instance
        return $this;
    }

    /**
     * Setup a one to many relationship with another model.
     * @param string $model Related model classname with namespace. You can use `ModelName::class` to get this property correctly.
     * @param string $column (Optional) Foreign key column name of the current model in the related table. Leave empty for auto.
     * @param string $name (Optional) Name of this relation to add to the query results. Leave empty for auto.
     * @param callable|null $callback (Optional) A function to interact with the related model before querying the relationship.\
     * It receives the related Model instance as the first parameter, and the current row as an associative array.
     * @return $this Current Model instance for nested calls.
     */
    public function hasMany(string $model, string $column = '', string $name = '', ?callable $callback = null)
    {
        // Get primary key and names
        $primary = $this->getPrimaryName();
        if (Util::isEmpty($name)) $name = Util::snakeCase(Util::pluralize(Util::classname($model)));
        if (Util::isEmpty($column)) $column = Util::snakeCase(Util::singularize(Util::classname($this))) . '_' . $primary;

        // Set to relations array
        $this->_relations[$name] = [
            'type' => 'many',
            'model' => $model,
            'primary' => $primary,
            'column' => $column,
            'callback' => $callback
        ];

        // Return instance
        return $this;
    }

    /**
     * Setup a many to one relationship with another model.
     * @param string $model Related model classname with namespace. You can use `ModelName::class` to get this property correctly.
     * @param string $column (Optional) Foreign key column name of the related model in the current table. Leave empty for auto.
     * @param string $name (Optional) Name of this relation to add to the query results. Leave empty for auto.
     * @param callable|null $callback (Optional) A function to interact with the related model before querying the relationship.\
     * It receives the related Model instance as the first parameter, and the current row as an associative array.
     * @return $this Current Model instance for nested calls.
     */
    public function belongsTo(string $model, string $column = '', string $name = '', ?callable $callback = null)
    {
        // Get primary key and names
        $primary = (new $model([], false))->getPrimaryName();
        if (Util::isEmpty($name)) $name = Util::snakeCase(Util::singularize(Util::classname($model)));
        if (Util::isEmpty($column)) $column = Util::snakeCase(Util::singularize(Util::classname($model))) . '_' . $primary;

        // Set to relations array
        $this->_relations[$name] = [
            'type' => 'belongs',
            'model' => $model,
            'primary' => $primary,
            'column' => $column,
            'callback' => $callback
        ];

        // Return instance
        return $this;
    }

    /**
     * Setup a many to many relationship with another model. **This requires an intermediate (pivot) table.**
     * @param string $model Related model classname with namespace. You can use `ModelName::class` to get this property correctly.
     * @param string $pivot (Optional) Intermediate table name. Leave empty for auto.
     * @param string $column (Optional) Foreign key column name of the current model in the pivot table. Leave empty for auto.
     * @param string $foreign (Optional) Foreign key column name of the related model in the pivot table. Leave empty for auto.
     * @param string $name (Optional) Name of this relation to add to the query results. Leave empty for auto.
     * @param callable|null $callback (Optional) A function to interact with the related model before querying the relationship.\
     * It receives the related Model instance as the first parameter, the current row as an associative array and the pivot table instance.
     * @param string $pivotName (Optional) Name of the pivot attribute in the relationship rows.
     * @return $this Current Model instance for nested calls.
     */
    public function belongsToMany(string $model, string $pivot = '', string $column = '', string $foreign = '', string $name = '', ?callable $callback = null, string $pivotName = 'pivot')
    {
        // Get primary key and names
        $instance = new $model([], false);
        $primary = $this->getPrimaryName();
        $primaryTarget = $instance->getPrimaryName();
        if (Util::isEmpty($name)) $name = Util::snakeCase(Util::pluralize(Util::classname($model)));
        if (Util::isEmpty($pivot)) $pivot = $this->getTable() . '_' . $instance->getTable();

        // Get foreign keys
        if (Util::isEmpty($column)) $column = Util::snakeCase(Util::singularize(Util::classname($this))) . '_' . $primary;
        if (Util::isEmpty($foreign)) $foreign = Util::snakeCase(Util::singularize(Util::classname($model))) . '_' . $primaryTarget;

        // Set to relations array
        $this->_relations[$name] = [
            'type' => 'belongs-many',
            'model' => $model,
            'primary-current' => $primary,
            'primary-target' => $primaryTarget,
            'pivot' => $pivot,
            'current-foreign' => $column,
            'target-foreign' => $foreign,
            'callback' => $callback,
            'pivot-name' => $pivotName
        ];

        // Return instance
        return $this;
    }

    /**
     * Setup a one to one relationship with another model using an intermediate model.
     * @param string $model Related model classname with namespace. You can use `ModelName::class` to get this property correctly.
     * @param string $intermediate Intermediate model classname with namespace. You can use `ModelName::class` to get this property correctly.
     * @param string $foreignCurrent (Optional) Foreign key column name of the current model in the intermediate table. Leave empty for auto.
     * @param string $foreignTarget (Optional) Foreign key column name of the intermediate model in the related table. Leave empty for auto.
     * @param string $name (Optional) Name of this relation to add to the query results. Leave empty for auto.
     * @param callable|null $callback $callback (Optional) A function to interact with the related and intermediate models before querying the relationship.\
     * It receives the related Model instance as the first parameter, the current row as an associative array and the intermediate model instance.
     * @return $this Current Model instance for nested calls.
     */
    public function hasOneThrough(string $model, string $intermediate, string $foreignCurrent = '', string $foreignTarget = '', string $name = '', ?callable $callback = null)
    {
        // Get primary key and names
        $primary = $this->getPrimaryName();
        $primaryIntermediate = (new $intermediate([], false))->getPrimaryName();
        $primaryTarget = (new $model([], false))->getPrimaryName();
        if (Util::isEmpty($name)) $name = Util::snakeCase(Util::singularize(Util::classname($model)));

        // Get foreign keys
        if (Util::isEmpty($foreignCurrent)) $foreignCurrent = Util::snakeCase(Util::singularize(Util::classname($this))) . '_' . $primary;
        if (Util::isEmpty($foreignTarget)) $foreignTarget = Util::snakeCase(Util::singularize(Util::classname($intermediate))) . '_' . $primaryIntermediate;

        // Set to relations array
        $this->_relations[$name] = [
            'type' => 'one-through',
            'model' => $model,
            'intermediate' => $intermediate,
            'primary-current' => $primary,
            'primary-intermediate' => $primaryIntermediate,
            'primary-target' => $primaryTarget,
            'current-foreign' => $foreignCurrent,
            'target-foreign' => $foreignTarget,
            'callback' => $callback,
        ];

        // Return instance
        return $this;
    }

    /**
     * Setup a one to many relationship with another model using an intermediate model.
     * @param string $model Related model classname with namespace. You can use `ModelName::class` to get this property correctly.
     * @param string $intermediate Intermediate model classname with namespace. You can use `ModelName::class` to get this property correctly.
     * @param string $foreignCurrent (Optional) Foreign key column name from the current model in the intermediate table. Leave empty for auto.
     * @param string $foreignTarget (Optional) Foreign key column name from the intermediate model in the target table. Leave empty for auto.
     * @param string $name (Optional) Name of this relation to add to the query results. Leave empty for auto.
     * @param callable|null $callback $callback (Optional) A function to interact with the related and intermediate models before querying the relationship.\
     * It receives the related Model instance as the first parameter, the current row as an associative array and the intermediate model instance.
     * @return $this Current Model instance for nested calls.
     */
    public function hasManyThrough(string $model, string $intermediate, string $foreignCurrent = '', string $foreignTarget = '', string $name = '', ?callable $callback = null)
    {
        // Get primary key and names
        $primary = $this->getPrimaryName();
        $primaryIntermediate = (new $intermediate([], false))->getPrimaryName();
        $primaryTarget = (new $model([], false))->getPrimaryName();
        if (Util::isEmpty($name)) $name = Util::snakeCase(Util::pluralize(Util::classname($model)));

        // Get foreign keys
        if (Util::isEmpty($foreignCurrent)) $foreignCurrent = Util::snakeCase(Util::singularize(Util::classname($this))) . '_' . $primary;
        if (Util::isEmpty($foreignTarget)) $foreignTarget = Util::snakeCase(Util::singularize(Util::classname($intermediate))) . '_' . $primaryIntermediate;

        // Set to relations array
        $this->_relations[$name] = [
            'type' => 'many-through',
            'model' => $model,
            'intermediate' => $intermediate,
            'primary-current' => $primary,
            'primary-intermediate' => $primaryIntermediate,
            'primary-target' => $primaryTarget,
            'current-foreign' => $foreignCurrent,
            'target-foreign' => $foreignTarget,
            'callback' => $callback,
        ];

        // Return instance
        return $this;
    }

    /**
     * Gets the primary key value from the model entity.
     * @return mixed Returns the primary key value, null otherwise.
     */
    public function getPrimary()
    {
        return $this->get($this->_primaryKey);
    }

    /**
     * Gets the name of the model primary key.
     * @return string Model primary key name.
     */
    public function getPrimaryName()
    {
        return $this->_primaryKey;
    }

    /**
     * Gets the model table name.
     * @return string Table name.
     */
    public function getTable()
    {
        return $this->_table;
    }

    /**
     * Checks if a given field is retrievable from the model.
     * @param string $field Field name to search for.
     * @return bool Returns true or false.
     */
    public function isRetrievable(string $field)
    {
        return empty($this->_fields) || in_array($field, $this->_fields);
    }

    /**
     * Checks if a given field is updatable in the model.
     * @param string $field Field name to search for.
     * @return bool Returns true or false.
     */
    public function isUpdatable(string $field)
    {
        return empty($this->_updatable) || in_array($field, $this->_updatable);
    }

    /**
     * Disables data casting.
     * @return $this Current Model instance for nested calls.
     */
    public function withoutCasting()
    {
        $this->_castingEnabled = false;
        return $this;
    }

    /**
     * Enables data casting.
     * @return $this Current Model instance for nested calls.
     */
    public function withCasting()
    {
        $this->_castingEnabled = true;
        return $this;
    }

    /**
     * Casts data types of fields from a row or an array of rows using model casts setting.
     * @param mixed $data A single row as an Element/associative array or a multi-dimensional of rows.
     * @return mixed Returns the data with the casted fields.
     */
    private function castData($data)
    {
        // Checks if casting is disabled, or data / casts property is empty
        if (!$this->_castingEnabled || empty($data) || empty($this->_casts)) return $data;

        // Checks for Collection
        $isCollection = $data instanceof Collection;
        if ($isCollection) $data = $data->toArray();

        // Checks if is an array of rows
        if (is_array($data) && !Util::isAssociativeArray($data)) {
            $result = [];
            foreach ($data as $item) $result[] = $this->castData($item);
            if ($isCollection) $result = new Collection($result);
            return $result;
        }

        // Converts the Element to an array
        $isElement = $data instanceof Element;
        if ($isElement) $data = $data->toArray();

        // Performs the castings
        foreach ($this->_casts as $field => $type) {
            // Checks for the field
            if (array_key_exists($field, $data)) {
                $params = explode(':', $type, 2);
                $type = strtolower($params[0]);

                // Checks if data is not null
                if (is_null($data[$field])) continue;

                // Gets the rule
                switch ($type) {
                    case 'array':
                        $data[$field] = json_decode($data[$field], true) ?? [];
                        break;

                    case 'collection':
                        $json = json_decode($data[$field], true) ?? [];
                        $data[$field] = new Collection($json);
                        break;

                    case 'set':
                        $data[$field] = explode(',', $data[$field], $params[1] ?? PHP_INT_MAX) ?? [];
                        break;

                    case 'decimal':
                        $data[$field] = round($data[$field], $params[1] ?? 0);
                        break;

                    case 'json':
                    case 'element':
                        $json = json_decode($data[$field], true) ?? [];
                        $data[$field] = new Element($json);
                        break;

                    case 'encrypted':
                        $data[$field] = Util::decryptString($data[$field], 'sha256', $params[1] ?? null);
                        break;

                    case 'serialized':
                        $data[$field] = is_null($data[$field]) ? null : unserialize($data[$field]);
                        break;

                    case 'callback':
                        if (empty($params[1])) throw new Exception('Missing function name in callback casting for "' . $field . '" field in "' . get_class($this) . '"');
                        if (is_callable([$this, $params[1]])) {
                            $data[$field] = call_user_func_array([$this, $params[1]], [$data[$field]]);
                        } else {
                            throw new BadMethodCallException('Method "' . $params[1] . '()" is not defined in "' . get_class($this) . '"');
                        }
                        break;

                    case 'date':
                        if (!empty($params[1])) {
                            $data[$field] = date($params[1], strtotime($data[$field]));
                        } else {
                            $data[$field] = new DateTime($data[$field]);
                        }
                        break;

                    default:
                        settype($data[$field], $type);
                        break;
                }
            }
        }

        // Returns the result
        return $isElement ? new Element($data) : $data;
    }

    /**
     * Performs data relations with other models.
     * @param mixed $data An Element, Collection or array of data to relate.
     * @return mixed Returns the data with all relations.
     */
    private function attachRelations($data)
    {
        // Checks if relations are enabled, or data / relations property is empty
        if ($this->_relationsEnabled === false || empty($data) || empty($this->_relations)) return $data;

        // Checks for Collection
        $isCollection = $data instanceof Collection;
        if ($isCollection) $data = $data->toArray();

        // Checks if is a single row or an array of rows
        $isElement = $data instanceof Element;
        $isSingle = $isElement || Util::isAssociativeArray($data);

        // Returns a single row
        if ($isSingle) {
            $data = $this->attachMultipleRelations([$data]);
            return $data[0] ?? null;
        }

        // Returns an array or Collection of rows
        $data = $this->attachMultipleRelations($data);
        return $isCollection ? new Collection($data) : $data;
    }

    /**
     * Attaches the relations of multiple rows.
     * @param array $data An array of rows to attach relations.
     * @return array Returns the data with the relations.
     */
    private function attachMultipleRelations(array $data)
    {
        // Converts data to a Collection
        $data = new Collection($data);

        // Gets the enabled relations
        $relationsEnabled = $this->getNestedRelationships();

        // Performs the relations
        foreach ($relationsEnabled as $name => $item) {
            switch ($item['type']) {
                // hasOne or hasMany relationship
                case 'one':
                case 'many':
                    // Gets the primary keys from the current model
                    $keys = $data->column($item['primary'])->unique();
                    if ($keys->isEmpty()) break;

                    // Creates the related model and run the callback
                    $table = new $item['model'];
                    if (!is_null($item['callback'])) call_user_func_array($item['callback'], [&$table, &$data]);

                    // Gets nested relationships
                    if (!empty($item['nested'])) $table->withRelations($item['nested']);

                    // Gets select statement
                    if (!empty($item['select'])) $table->select($item['select']);

                    // Gets the relationships from the target model
                    $relations = $table->allBy($item['column'], $keys);
                    if ($relations->isEmpty()) break;

                    // Loops through the rows
                    foreach ($data as $idx => $row) {
                        $isElement = $row instanceof Element;

                        // Parses the foreign value from the row
                        if ($isElement) {
                            $value = $row->get($item['primary'], null);
                        } else {
                            $value = $row[$item['primary']] ?? null;
                        }

                        // Gets the relationship value
                        if (!is_null($value)) {
                            if ($item['type'] === 'one') {
                                $rel = $relations->search($item['column'], $value);
                                $value = !empty($rel) ? $rel : null;
                            } else {
                                $value = $relations->filter(fn($i) => $i->get($item['column']) == $value)->values();
                            }
                        }

                        // Sets the value back to the row
                        if ($isElement) {
                            $row->set($name, $value);
                        } else {
                            $row[$name] = $value;
                        }

                        // Change the row
                        $data[$idx] = $row;
                    }

                    break;

                // belongsTo relationship
                case 'belongs':
                    // Gets the foreign keys from the target model
                    $keys = $data->column($item['column'])->unique();
                    if ($keys->isEmpty()) break;

                    // Creates the related model and run the callback
                    $table = new $item['model'];
                    if (!is_null($item['callback'])) call_user_func_array($item['callback'], [&$table, &$data]);

                    // Gets nested relationships
                    if (!empty($item['nested'])) $table->withRelations($item['nested']);

                    // Gets select statement
                    if (!empty($item['select'])) $table->select($item['select']);

                    // Gets the relationships from the target model
                    $relations = $table->allBy($item['primary'], $keys);
                    if ($relations->isEmpty()) break;

                    // Loops through the rows
                    foreach ($data as $idx => $row) {
                        $isElement = $row instanceof Element;

                        // Parses the foreign value from the row
                        if ($isElement) {
                            $value = $row->get($item['column'], null);
                        } else {
                            $value = $row[$item['column']] ?? null;
                        }

                        // Gets the relationship value
                        if (!is_null($value)) {
                            $rel = $relations->search($item['primary'], $value);
                            $value = !Util::isEmpty($rel) ? $rel : null;
                        }

                        // Sets the value back to the row
                        if ($isElement) {
                            $row->set($name, $value);
                        } else {
                            $row[$name] = $value;
                        }

                        // Change the row
                        $data[$idx] = $row;
                    }

                    break;

                // belongsToMany, hasOneThrough or hasManyThrough relationship
                case 'belongs-many':
                case 'one-through':
                case 'many-through':
                    // Gets the primary keys from the current model
                    $currentKeys = $data->column($item['primary-current'])->unique();
                    if ($currentKeys->isEmpty()) break;

                    // Creates the related model and pivot and run the callback
                    $table = new $item['model'];
                    $pivot = $item['type'] === 'belongs-many' ? new Kraken($item['pivot'], $this->_database) : new $item['intermediate'];
                    if (!is_null($item['callback'])) call_user_func_array($item['callback'], [&$table, &$data, &$pivot]);

                    // Gets nested relationships
                    if (!empty($item['nested'])) $table->withRelations($item['nested']);

                    // Gets select statement
                    if (!empty($item['select'])) $table->select($item['select']);

                    // Gets the foreign keys from the current model in the pivot
                    $pivotRelations = $pivot->whereIn($item['current-foreign'], $currentKeys);
                    $pivotRelations = $item['type'] === 'belongs-many' ? $pivotRelations->fetchAll() : $pivotRelations->all();
                    if ($pivotRelations->isEmpty()) break;

                    // Gets the foreign keys from the target model in the pivot relations
                    $fk = $item['type'] === 'belongs-many' ? $item['target-foreign'] : $item['primary-intermediate'];
                    $targetKeys = $pivotRelations->column($fk)->unique();
                    if ($targetKeys->isEmpty()) break;

                    // Gets the relations between the tables
                    $fk2 = $item['type'] === 'belongs-many' ? $item['primary-target'] : $item['target-foreign'];
                    $relations = $table->allBy($fk2, $targetKeys);
                    if ($relations->isEmpty()) break;

                    // Loops through the rows
                    foreach ($data as $idx => $row) {
                        $isElement = $row instanceof Element;

                        // Parses the value from the row
                        if ($isElement) {
                            $value = $row->get($item['primary-current'], null);
                        } else {
                            $value = $row[$item['primary-current']] ?? null;
                        }

                        // Gets the relationships
                        if (!is_null($value)) {
                            // Gets the matching keys from the pivot table
                            $pivotValues = $pivotRelations->filter(fn($i) => $i->get($item['current-foreign']) == $value)->values();

                            // Get the related instances
                            if ($pivotValues->isNotEmpty()) {
                                // belongsToMany()
                                if ($item['type'] === 'belongs-many') {
                                    // Gets the target model ids from the pivot table
                                    $keys = $pivotValues->column($item['target-foreign'])->toArray();

                                    // Filters the relationships in the target model
                                    $value = $relations->filter(fn($i) => in_array($i->get($item['primary-target']), $keys))
                                        ->each(function ($m) use ($pivotValues, $item) {
                                            // Attaches the pivot object
                                            $pv = $pivotValues->filter(fn($r) => $r->get($item['target-foreign']) == $m->get($item['primary-target']))->first();
                                            $m->set($item['pivot-name'], $pv);
                                        })
                                        ->values();
                                } else {
                                    // hasOneThrough() or hasManyThrough()
                                    $keys = $pivotValues->column($item['primary-intermediate'])->toArray();
                                    $value = $relations->filter(fn($i) => in_array($i->get($item['target-foreign']), $keys))->values();
                                    if ($item['type'] === 'one-through') $value = $value->first();
                                }
                            } else {
                                // Values were not found
                                $value = $item['type'] === 'one-through' ? null : new Collection();
                            }
                        }

                        // Sets the value back to the row
                        if ($isElement) {
                            $row->set($name, $value);
                        } else {
                            $row[$name] = $value;
                        }

                        // Change the row
                        $data[$idx] = $row;
                    }

                    break;
            }
        }

        // Returns the result as array
        return $data->toArray();
    }

    /**
     * Parses a list of enabled relationships with nesting and select statements support.
     * @return array Returns an associative array with the relationships.
     */
    private function getNestedRelationships()
    {
        // If all relations are enabled, return everything
        if (empty($this->_relationsEnabled)) {
            return $this->_relations;
        }

        $relationsEnabled = [];

        foreach ($this->_relationsEnabled as $rel) {
            // Parse nested relationships, if any
            if (Util::stringContains($rel, '.')) {
                $modelSegments = explode('.', $rel, 2);
                $selectSegments = explode(':', $modelSegments[0], 2);
                $key = $selectSegments[0];

                // Checks if the relationship exists
                if (empty($this->_relations[$key])) throw new Exception("Relationship \"$key\" is not defined in the model");

                // Checks if the key already exists
                if (empty($relationsEnabled[$key])) {
                    $relationsEnabled[$key] = $this->_relations[$key];
                    $relationsEnabled[$key]['select'] = [];
                    $relationsEnabled[$key]['nested'] = [];
                }

                // Merges the select statements
                if (!empty($selectSegments[1])) {
                    $relationsEnabled[$key]['select'] = array_unique(array_merge($relationsEnabled[$key]['select'], explode(',', $selectSegments[1])));
                }

                // Merges the nesting
                if (!empty($modelSegments[1])) {
                    $relationsEnabled[$key]['nested'][] = $modelSegments[1];
                }
            } else if (Util::stringContains($rel, ':')) {
                // Parse select statements, if any
                $selectSegments = explode(':', $rel, 2);
                $key = $selectSegments[0];

                // Checks if the relationship exists
                if (empty($this->_relations[$key])) throw new Exception("Relationship \"$key\" is not defined in the model");

                // Checks if the key already exists
                if (empty($relationsEnabled[$key])) {
                    $relationsEnabled[$key] = $this->_relations[$key];
                    $relationsEnabled[$key]['select'] = [];
                }

                // Merges the select statements
                if (!empty($selectSegments[1])) {
                    $relationsEnabled[$key]['select'] = array_unique(array_merge($relationsEnabled[$key]['select'], explode(',', $selectSegments[1])));
                }
            } else {
                // Checks if the relationship exists
                if (empty($this->_relations[$rel])) throw new Exception("Relationship \"$rel\" is not defined in the model");

                // Checks if the key already exists
                if (empty($relationsEnabled[$rel])) {
                    $relationsEnabled[$rel] = $this->_relations[$rel];
                    $relationsEnabled[$rel]['select'] = [];
                }
            }
        }

        // Returns the result
        return $relationsEnabled;
    }

    /**
     * Disables data mutation.
     * @return $this Current Model instance for nested calls.
     */
    public function withoutMutation()
    {
        $this->_mutateEnabled = false;
        return $this;
    }

    /**
     * Enables data mutation.
     * @return $this Current Model instance for nested calls.
     */
    public function withMutation()
    {
        $this->_mutateEnabled = true;
        return $this;
    }

    /**
     * Mutates data using model mutators setting.
     * @param array|Element $data An Element or associative array of data to mutate.
     * @return array|Element Returns the mutated data.
     */
    private function mutateData($data)
    {
        // Checks if data or mutators property is empty
        if (!$this->_mutateEnabled || empty($data) || empty($this->_mutators)) return $data;

        // Converts the element to an array
        $isElement = $data instanceof Element;
        if ($isElement) $data = $data->toArray();

        // Performs mutations
        foreach ($this->_mutators as $field => $mutator) {
            // Checks for the field
            if (array_key_exists($field, $data)) {
                $params = explode(':', $mutator, 2);
                $mutator = strtolower($params[0]);

                // Checks if data is not null
                if (is_null($data[$field])) continue;

                // Gets the rule
                switch ($mutator) {
                    case 'json':
                        $data[$field] = json_encode($data[$field], JSON_UNESCAPED_UNICODE);
                        break;

                    case 'encrypted':
                        $data[$field] = Util::encryptString($data[$field], 'sha256', $params[1] ?? null);
                        break;

                    case 'serialized':
                        $data[$field] = is_null($data[$field]) ? null : serialize($data[$field]);
                        break;

                    case 'callback':
                        if (empty($params[1])) throw new Exception('Missing function name in callback mutator for "' . $field . '" field in "' . get_class($this) . '"');
                        if (is_callable([$this, $params[1]])) {
                            $data[$field] = call_user_func_array([$this, $params[1]], [$data[$field]]);
                        } else {
                            throw new BadMethodCallException('Method "' . $params[1] . '()" is not defined in "' . get_class($this) . '"');
                        }
                        break;
                }
            }
        }

        // Returns the result
        return $isElement ? new Element($data) : $data;
    }

    /**
     * Filters an array of data returning only the fields in the model updatable setting.
     * @param array $data An associative array of fields and values to filter.
     * @return array Returns the filtered array.
     */
    private function filterData(array $data)
    {
        if (empty($data) || empty($this->_updatable)) return $data;
        $allowedFields = array_merge($this->_updatable, array_map(fn($k) => $this->_table . '.' . $k, $this->_updatable));
        return array_intersect_key($data, array_flip($allowedFields));
    }

    /**
     * Sets fields filters in the query.
     * @param mixed $field Field name or associative array/Collection relating fields and values.
     * @param mixed $value (Optional) Value to search for.
     */
    private function filterFields($field, $value = null)
    {
        if ($field instanceof Collection) $field = $field->toArray();
        if (Util::isAssociativeArray($field)) {
            foreach ($field as $key => $value) {
                if (!Util::stringContains($key, '.')) $key = $this->_table . '.' . $key;
                if (is_array($value)) {
                    $this->whereIn($key, $value);
                } else if ($value === 'NULL' || is_null($value)) {
                    $this->whereNull($key);
                } else {
                    $this->where($key, $value);
                }
            }
        } else {
            if (!Util::stringContains($field, '.')) $field = $this->_table . '.' . $field;
            if (is_array($value)) {
                $this->whereIn($field, $value);
            } else if ($value === 'NULL' || is_null($value)) {
                $this->whereNull($field);
            } else {
                $this->where($field, $value);
            }
        }
    }
}
