<?php
    namespace Glowie\Core\Database;

    use BadMethodCallException;
    use Glowie\Core\Element;
    use Glowie\Core\Traits\ElementTrait;
    use Glowie\Core\Collection;
    use Util;
    use Exception;
    use JsonSerializable;

    /**
     * Model core for Glowie application.
     * @category Model
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) Glowie
     * @license MIT
     * @link https://gabrielsilva.dev.br/glowie
     * @see https://gabrielsilva.dev.br/glowie/docs/latest/forms-and-data/models
     */
    class Model extends Kraken implements JsonSerializable{
        use ElementTrait;

        /**
         * Model table name.
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
         * Creates a new instance of the model.
         * @param Element|array $data An Element or associative array with the initial data to fill the model entity.\
         * This data will be merged into the initial model attributes, if filled.
         */
        final public function __construct($data = []){
            // Gets the table name
            if(Util::isEmpty($this->_table)) $this->_table = Util::snakeCase(Util::classname($this));

            // Constructs the query builder
            Kraken::__construct($this->_table, $this->_database);

            // Sets the initial data
            if(is_callable([$data, 'toArray'])) $data = $data->toArray();
            $data = array_merge($this->_attributes, $data);
            if(!empty($data)) $this->fill($data);
        }

        /**
         * Calls a magic method.
         * @param string $name Method name.
         * @param array $args Method arguments.
         * @return $this Current instance for nested calls.
         */
        public function __call(string $name, array $args){
            // Magic where()
            if(Util::startsWith($name, 'where')){
                $field = Util::snakeCase(Util::replaceFirst($name, 'where', ''));
                return $this->where($field, $args[0] ?? null, $args[1] ?? null, $args[2] ?? 'AND');

            // Magic orWhere()
            }else if(Util::startsWith($name, 'orWhere')){
                $field = Util::snakeCase(Util::replaceFirst($name, 'orWhere', ''));
                return $this->orWhere($field, $args[0] ?? null, $args[1] ?? null);

            // Magic findBy()
            }else if(Util::startsWith($name, 'findBy')){
                $field = Util::snakeCase(Util::replaceFirst($name, 'findBy', ''));
                return $this->findBy($field, $args[0] ?? null, $args[1] ?? false);

            // Magic findAndFillBy()
            }else if(Util::startsWith($name, 'findAndFillBy')){
                $field = Util::snakeCase(Util::replaceFirst($name, 'findAndFillBy', ''));
                return $this->findAndFillBy($field, $args[0] ?? null, $args[1] ?? false, $args[2] ?? false);

            // Magic allBy()
            }else if(Util::startsWith($name, 'allBy')){
                $field = Util::snakeCase(Util::replaceFirst($name, 'allBy', ''));
                return $this->allBy($field, $args[0] ?? null, $args[1] ?? false);

            // Magic dropBy()
            }else if(Util::startsWith($name, 'dropBy')){
                $field = Util::snakeCase(Util::replaceFirst($name, 'allBy', ''));
                return $this->dropBy($field, $args[0] ?? null, $args[1] ?? false);

            // Method not found
            }else{
                throw new BadMethodCallException(sprintf('Call to undefined method %s::%s()', get_class($this), $name));
            }
        }

        /**
         * Fetches the first result from a SELECT query. Results will be casted, if available.
         * @return mixed Returns the first resulting row on success or null if not found.
         * @throws QueryException Throws an exception if the query fails.
         */
        public function fetchRow(){
            return $this->castData(Kraken::fetchRow());
        }

        /**
         * Fetches all results from a SELECT query. Results will be casted, if available.
         * @return Collection Returns a Collection with all resulting rows.
         * @throws QueryException Throws an exception if the query fails.
         */
        public function fetchAll(){
            return $this->castData(Kraken::fetchAll());
        }

        /**
         * Gets the first row that matches the model primary key value.
         * @param mixed $primary (Optional) Primary key value to search for.
         * @param bool $deleted (Optional) Include deleted rows (if soft deletes enabled).
         * @return mixed Returns the row on success or null if not found.
         */
        public function find($primary = null, bool $deleted = false){
            $fields = !Util::isEmpty($this->_select) ? $this->_select : (!Util::isEmpty($this->_fields) ? $this->_fields : '*');
            if(!is_null($primary)) $this->where($this->_table . '.' . $this->_primaryKey, $primary);
            if($this->_softDeletes && !$deleted) $this->whereNull($this->_table . '.' . $this->_deletedField);
            return $this->select($fields)->fetchRow();
        }

        /**
         * Gets the first row that matches a field value.
         * @param string|array $field Field name to use while searching or an associative array relating fields and values to search.
         * @param mixed $value (Optional) Value to search for.
         * @param bool $deleted (Optional) Include deleted rows (if soft deletes enabled).
         * @return mixed Returns the row on success or null if not found.
         */
        public function findBy($field, $value = null, bool $deleted = false){
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
        public function findAndFill($primary = null, bool $deleted = false, bool $overwrite = false){
            $result = $this->find($primary, $deleted);
            if(!$result) return false;
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
        public function findAndFillBy($field, $value = null, bool $deleted = false, bool $overwrite = false){
            $result = $this->findBy($field, $value, $deleted);
            if(!$result) return false;
            return $this->fill($result, $overwrite);
        }

        /**
         * Gets all rows from the model table.
         * @param bool $deleted (Optional) Include deleted rows (if soft deletes enabled).
         * @return Collection Returns a Collection with all rows.
         */
        public function all(bool $deleted = false){
            $fields = !Util::isEmpty($this->_select) ? $this->_select : (!Util::isEmpty($this->_fields) ? $this->_fields : '*');
            if($this->_softDeletes && !$deleted) $this->whereNull($this->_table . '.' . $this->_deletedField);
            return $this->select($fields)->fetchAll();
        }

        /**
         * Gets filtered rows matching a field value.
         * @param string|array $field Field name to use while searching or an associative array relating fields and values to search.
         * @param mixed $value (Optional) Value to search for.
         * @param bool $deleted (Optional) Include deleted rows (if soft deletes enabled).
         * @return Collection Returns a Collection with the filtered rows.
         */
        public function allBy($field, $value = null, bool $deleted = false){
            $this->filterFields($field, $value);
            return $this->all($deleted);
        }

        /**
         * Gets all rows from the model table ordering by the newest **created at** field.
         * @param bool $deleted (Optional) Include deleted rows (if soft deletes enabled).
         * @return Collection Returns a Collection with all rows.
         * @throws Exception Throws an exception if the model is not handling timestamp fields.
         */
        public function latest(bool $deleted = false){
            if(!$this->_timestamps) throw new Exception('latest(): Model "' . get_class($this) . '" is not handling timestamp fields');
            return $this->orderBy($this->_table . '.' . $this->_createdField, 'DESC')->all($deleted);
        }

        /**
         * Gets all rows from the model table ordering by the oldest **created at** field.
         * @param bool $deleted (Optional) Include deleted rows (if soft deletes enabled).
         * @return Collection Returns a Collection with all rows.
         * @throws Exception Throws an exception if the model is not handling timestamp fields.
         */
        public function oldest(bool $deleted = false){
            if(!$this->_timestamps) throw new Exception('oldest(): Model "' . get_class($this) . '" is not handling timestamp fields');
            return $this->orderBy($this->_table . '.' . $this->_createdField, 'ASC')->all($deleted);
        }

        /**
         * Deletes the first row that matches the model primary key value.
         * @param mixed $primary (Optional) Primary key value to search for. You can also use an array of values.
         * @param bool $force (Optional) Bypass soft deletes (if enabled) and permanently delete the row.
         * @return bool Returns true on success or false on failure.
         */
        public function drop($primary = null, bool $force = false){
            if(!is_null($primary)) $this->whereIn($this->_table . '.' . $this->_primaryKey, (array)$primary);
            if($this->_softDeletes && !$force){
                return $this->update([$this->_table . '.' . $this->_deletedField => self::raw('NOW()')]);
            }else{
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
        public function dropBy($field, $value = null, bool $force = false){
            $this->filterFields($field, $value);
            if($this->_softDeletes && !$force){
                return $this->update([$this->_table . '.' . $this->_deletedField => self::raw('NOW()')]);
            }else{
                return $this->delete($this->_table);
            }
        }

        /**
         * Permanently removes soft deleted rows (if enabled).
         * @return bool Returns true on success or false on failure.
         * @throws Exception Throws an exception if the model soft deletes are not enabled.
         */
        public function purge(){
            if(!$this->_softDeletes) throw new Exception('purge(): Model "' . get_class($this) . '" soft deletes are not enabled');
            $this->clearQuery();
            $this->whereNotNull($this->_table . '.' . $this->_deletedField);
            return $this->delete();
        }

        /**
         * Gets only a list of soft deleted rows (if enabled).
         * @return Collection Returns a Collection with the filtered rows.
         */
        public function getDeleted(){
            if(!$this->_softDeletes) throw new Exception('getDeleted(): Model "' . get_class($this) . '" soft deletes are not enabled');
            $this->whereNotNull($this->_table . '.' . $this->_deletedField);
            return $this->all(true);
        }

        /**
         * Inserts a new row in the model table.
         * @param mixed $data An Element or associative array/Collection relating fields and values to insert.
         * @return mixed Returns the last inserted `AUTO_INCREMENT` value (or true) on success or false on failure.\
         * If the model uses UUIDs, upon success the last generated UUID will be returned.
         */
        public function create($data){
            // Clears the current built query
            $this->clearQuery();

            // Parse data and timestamps
            if(is_callable([$data, 'toArray'])) $data = $data->toArray();
            $data = $this->mutateData($this->filterData($data));
            if($this->_timestamps){
                $data[$this->_createdField] = Kraken::raw('NOW()');
                $data[$this->_updatedField] = Kraken::raw('NOW()');
            }

            // Generate UUID if in use
            if($this->_uuid) $data[$this->_primaryKey] = $data[$this->_primaryKey] ?? Util::uuid();

            // Inserts the element
            $result = $this->insert($data);

            // Return result
            if($result === false) return $result;
            if(isset($data[$this->_primaryKey])) return $data[$this->_primaryKey];
            if($this->lastInsertId()) return $this->lastInsertId();
            return $result;
        }

        /**
         * Gets the first row that matches a set of fields and values. If no matching row is found, a new one is created and returned.
         * @param array $find Associative array of fields and values to search.
         * @param array $create (Optional) Associative array of data to merge into the `$find` fields to create a new row.
         * @return mixed Returns the existing or new row, false on error.
         */
        public function findOrCreate(array $find, array $create = []){
            $row = $this->findBy($find);
            if($row) return $row;
            $id = $this->create(array_merge($find, $create));
            if(!$id) return false;
            return $this->find($id);
        }

        /**
         * Checks if a row matches the primary key value in the data. If so, updates the row. Otherwise,\
         * inserts a new record in the model table.
         * @param Element|array $data An Element or associative array relating fields and values to upsert. **Must include the primary key field to update.**
         * @return mixed Returns the last inserted `AUTO_INCREMENT` value (or true) if the row is created, otherwise returns true on success or false on failure.
         */
        public function updateOrCreate($data){
            // Clears the current built query
            $this->clearQuery();

            // Checks if the primary key was passed and matches an existing row
            if(is_callable([$data, 'toArray'])) $data = $data->toArray();
            if(isset($data[$this->_primaryKey]) && $this->find($data[$this->_primaryKey])){
                // Parse data and timestamps
                $updatedData = $this->mutateData($this->filterData($data));
                if($this->_timestamps) $updatedData[$this->_updatedField] = Kraken::raw('NOW()');

                // Updates the element
                return $this->where($this->_primaryKey, $data[$this->_primaryKey])->update($updatedData);
            }else{
                // Inserts the element
                return $this->create($data);
            }
        }

        /**
         * Fills the model entity with a row data. This data will be merged into the existing model data, if any.
         * @param Element|array $row An Element or associative array with the row data to fill.
         * @param bool $overwrite (Optional) Set to `true` to overwrite the existing model data instead of merging.
         * @return $this Current Model instance for nested calls.
         */
        public function fill($row, bool $overwrite = false){
            if(is_callable([$row, 'toArray'])) $row = $row->toArray();
            if(!$overwrite) $row = array_merge($this->toArray(), $row);
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
        public function isDirty(string $field = ''){
            if(!$this->_initialData) throw new Exception('isDirty(): Model "' . get_class($this) . '" entity was not filled with a row data');
            if(!Util::isEmpty($field)){
                return ($this->_initialData->get($field) !== $this->get($field));
            }else{
                return ($this->_initialData->toArray() !== $this->toArray());
            }
        }

        /**
         * Checks if the initial row data has not been modified in the model entity.
         * @param string $field (Optional) Field to check. Leave empty to compare everything.
         * @return bool Returns true if the row data has not been modified or false otherwise.
         * @throws Exception Throws an exception if the model entity is not filled with a row data.
         */
        public function isPristine(string $field = ''){
            if(!$this->_initialData) throw new Exception('isPristine(): Model "' . get_class($this) . '" entity was not filled with a row data');
            return !$this->isDirty($field);
        }

        /**
         * Resets the model entity back to the original filled data.\
         * **Note:** this will delete all modifications made to the model entity data.
         * @return $this Current Model instance for nested calls.
         */
        public function reset(){
            if(!$this->_initialData) throw new Exception('reset(): Model "' . get_class($this) . '" entity was not filled with a row data');
            return $this->fill($this->_initialData, true);
        }

        /**
         * Refreshes and refills the model entity data back from the database using its primary key.\
         * **Note:** this will delete all modifications made to the model entity data.
         * @param bool $deleted (Optional) Include deleted rows (if soft deletes enabled).
         * @return mixed Returns the current Model instance if the row is found, false otherwise.
         */
        public function refresh(bool $deleted = false){
            // Checks if filled model entity
            if(!$this->_initialData) throw new Exception('refresh(): Model "' . get_class($this) . '" entity was not filled with a row data');

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
        public function save(){
            $data = $this->toArray();
            if(empty($data)) throw new Exception('save(): Model "' . get_class($this) . '" entity cannot be empty while saving');
            $result = $this->updateOrCreate($data);

            // Parse primary key value
            if(is_bool($result)) return $result;
            $this->{$this->_primaryKey} = $result;
            return $result;
        }

        /**
         * Deletes the database row matching the model entity primary key value.
         * @return bool Returns true on success or false on failure.
         * @throws Exception Throws an exception if the model entity primary key is not filled.
         */
        public function destroy(){
            $primary = $this->getPrimary();
            if (is_null($primary)) throw new Exception('destroy(): Model "' . get_class($this) . '" entity primary key was not filled');
            return $this->drop($primary);
        }

        /**
         * Clones the current model entity removing the primary key and timestamp fields.
         * @param array $excluded (Optional) Array of aditional fields to delete from the model.
         * @return $this Returns a copy as a new instance of the current model.
         */
        public function clone(array $fields = []){
            $model = clone $this;
            $model->remove([$this->_primaryKey, $this->_createdField, $this->_updatedField, $this->_deletedField, ...$fields]);
            return $model;
        }

        /**
         * Gets the primary key value from the model entity.
         * @return mixed Returns the primary key value, null otherwise.
         */
        public function getPrimary(){
            return $this->get($this->_primaryKey);
        }

        /**
         * Gets the name of the model primary key.
         * @return string Model primary key name.
         */
        public function getPrimaryName(){
            return $this->_primaryKey;
        }

        /**
         * Gets the model table name.
         * @return string Table name.
         */
        public function getTable(){
            return $this->_table;
        }

        /**
         * Checks if a given field is retrievable from the model.
         * @param string $field Field name to search for.
         * @return bool Returns true or false.
         */
        public function isRetrievable(string $field){
            return empty($this->_fields) || in_array($field, $this->_fields);
        }

        /**
         * Checks if a given field is updatable in the model.
         * @param string $field Field name to search for.
         * @return bool Returns true or false.
         */
        public function isUpdatable(string $field){
            return empty($this->_updatable) || in_array($field, $this->_updatable);
        }

        /**
         * Casts data types of fields from a row or an array of rows using model casts setting.
         * @param mixed $data A single row as an Element/associative array or a multi-dimensional of rows.
         * @return mixed Returns the data with the casted fields.
         */
        private function castData($data){
            // Checks if data or casts property is empty
            if(empty($data) || empty($this->_casts)) return $data;

            // Checks for Collection
            $isCollection = $data instanceof Collection;
            if($isCollection) $data = $data->toArray();

            // Checks if is an array of rows
            if(is_array($data) && !Util::isAssociativeArray($data)){
                $result = [];
                foreach($data as $item) $result[] = $this->castData($item);
                if($isCollection) $result = new Collection($result);
                return $result;
            }

            // Converts the Element to an array
            $isElement = is_callable([$data, 'toArray']);
            if($isElement) $data = $data->toArray();

            // Performs the castings
            foreach($this->_casts as $field => $type){
                // Checks for the field
                if(array_key_exists($field, $data)){
                    $params = explode(':', $type, 2);
                    $type = strtolower($params[0]);

                    // Gets the rule
                    switch($type){
                        case 'array':
                            $data[$field] = json_decode($data[$field], true) ?? null;
                            break;

                        case 'decimal':
                            $data[$field] = round($data[$field], $params[1] ?? 0);
                            break;

                        case 'json':
                            $json = json_decode($data[$field], true);
                            if($json){
                                $data[$field] = new Element($json);
                            }else{
                                $data[$field] = null;
                            }
                            break;

                        case 'encrypted':
                            $data[$field] = Util::decryptString($data[$field], 'sha256', $params[1] ?? null);
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
                            if(!empty($params[1])){
                                $data[$field] = date($params[1], strtotime($data[$field]));
                            }else{
                                $data[$field] = strtotime($data[$field]);
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
         * Mutates data using model mutators setting.
         * @param array|Element $data An Element or associative array of data to mutate.
         * @return array|Element Returns the mutated data.
         */
        private function mutateData($data){
            // Checks if data or mutators property is empty
            if(empty($data) || empty($this->_mutators)) return $data;

            // Converts the element to an array
            $isElement = is_callable([$data, 'toArray']);
            if($isElement) $data = $data->toArray();

            // Performs mutations
            foreach($this->_mutators as $field => $mutator){
                // Checks for the field
                if(array_key_exists($field, $data)){
                    $params = explode(':', $mutator, 2);
                    $mutator = strtolower($params[0]);

                    // Gets the rule
                    switch($mutator){
                        case 'json':
                            $data[$field] = json_encode($data[$field], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
                            break;

                        case 'encrypted':
                            $data[$field] = Util::encryptString($data[$field], 'sha256', $params[1] ?? null);
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
        private function filterData(array $data){
            if(empty($data) || empty($this->_updatable)) return $data;
            return array_intersect_key($data, array_flip($this->_updatable));
        }

        /**
         * Sets fields filters in the query.
         * @param mixed $field Field name or associative array/Collection relating fields and values.
         * @param mixed $value (Optional) Value to search for.
         */
        private function filterFields($field, $value = null){
            if($field instanceof Collection) $field = $field->toArray();
            if(Util::isAssociativeArray($field)){
                foreach($field as $key => $value){
                    $key = $this->_table . '.' . $key;
                    if(is_array($value)){
                        $this->whereIn($key, $value);
                    }else if($value === 'NULL' || is_null($value)){
                        $this->whereNull($key);
                    }else{
                        $this->where($key, $value);
                    }
                }
            }else{
                $field = $this->_table . '.' . $field;
                if(is_array($value)){
                    $this->whereIn($field, $value);
                }else if($value === 'NULL' || is_null($value)){
                    $this->whereNull($field);
                }else{
                    $this->where($field, $value);
                }
            }
        }

    }

?>