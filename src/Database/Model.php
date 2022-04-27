<?php
    namespace Glowie\Core\Database;

    use BadMethodCallException;
    use Glowie\Core\Element;
    use Glowie\Core\Traits\ElementTrait;
    use Util;
    use Exception;

    /**
     * Model core for Glowie application.
     * @category Model
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.2
     */
    class Model extends Kraken{
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
         * Handle timestamp fields.
         * @var bool
         */
        protected $_timestamps = true;

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
            $data = array_merge($this->_attributes, $data);
            if(!empty($data)) $this->fill($data);
        }

        /**
         * Gets the first row that matches the model primary key value.
         * @param mixed $primary Primary key value to search for.
         * @param bool $deleted (Optional) Include deleted rows (if soft deletes enabled).
         * @return mixed Returns the row on success or null if not found.
         */
        public function find($primary, bool $deleted = false){
            $fields = !empty($this->_fields) ? $this->_fields : '*';
            if($this->_softDeletes && !$deleted) $this->whereNull($this->_deletedField);
            return $this->castData($this->select($fields)->where($this->_primaryKey, $primary)->fetchRow(), true);
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
            $fields = !empty($this->_fields) ? $this->_fields : '*';
            if($this->_softDeletes && !$deleted) $this->whereNull($this->_deletedField);
            return $this->castData($this->select($fields)->fetchRow(), true);
        }

        /**
         * Gets all rows from the model table.
         * @param bool $deleted (Optional) Include deleted rows (if soft deletes enabled).
         * @return array Returns an array with all rows.
         */
        public function all(bool $deleted = false){
            $fields = !empty($this->_fields) ? $this->_fields : '*';
            if($this->_softDeletes && !$deleted) $this->whereNull($this->_deletedField);
            return $this->castData($this->select($fields)->fetchAll());
        }

        /**
         * Gets filtered rows matching a field value.
         * @param string|array $field Field name to use while searching or an associative array relating fields and values to search.
         * @param mixed $value (Optional) Value to search for.
         * @param bool $deleted (Optional) Include deleted rows (if soft deletes enabled).
         * @return array Returns an array with the filtered rows.
         */
        public function allBy($field, $value = null, bool $deleted = false){
            $this->filterFields($field, $value);
            $fields = !empty($this->_fields) ? $this->_fields : '*';
            if($this->_softDeletes && !$deleted) $this->whereNull($this->_deletedField);
            return $this->castData($this->select($fields)->fetchAll());
        }

        /**
         * Gets all rows from the model table ordering by the newest **created at** field.
         * @param bool $deleted (Optional) Include deleted rows (if soft deletes enabled).
         * @return array Returns an array with all rows.
         * @throws Exception Throws an exception if the model is not handling timestamp fields.
         */
        public function latest(bool $deleted = false){
            if(!$this->_timestamps) throw new Exception('latest(): Model "' . get_class($this) . '" is not handling timestamp fields');
            $fields = !empty($this->_fields) ? $this->_fields : '*';
            if($this->_softDeletes && !$deleted) $this->whereNull($this->_deletedField);
            return $this->castData($this->select($fields)->orderBy($this->_createdField, 'DESC')->fetchAll());
        }

        /**
         * Gets all rows from the model table ordering by the oldest **created at** field.
         * @param bool $deleted (Optional) Include deleted rows (if soft deletes enabled).
         * @return array Returns an array with all rows.
         * @throws Exception Throws an exception if the model is not handling timestamp fields.
         */
        public function oldest(bool $deleted = false){
            if(!$this->_timestamps) throw new Exception('oldest(): Model "' . get_class($this) . '" is not handling timestamp fields');
            $fields = !empty($this->_fields) ? $this->_fields : '*';
            if($this->_softDeletes && !$deleted) $this->whereNull($this->_deletedField);
            return $this->castData($this->select($fields)->orderBy($this->_createdField, 'ASC')->fetchAll());
        }

        /**
         * Deletes the first row that matches the model primary key value.
         * @param mixed $primary Primary key value to search for. You can also use an array of values.
         * @param bool $force (Optional) Bypass soft deletes (if enabled) and permanently delete the row.
         * @return bool Returns true on success or false on failure.
         */
        public function drop($primary, bool $force = false){
            $this->whereIn($this->_primaryKey, (array)$primary);
            if($this->_softDeletes && !$force){
                return $this->update([$this->_deletedField = self::raw('NOW()')]);
            }else{
                return $this->delete();
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
                return $this->update([$this->_deletedField = self::raw('NOW()')]);
            }else{
                return $this->delete();
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
            $this->whereNotNull($this->_deletedField);
            return $this->delete();
        }

        /**
         * Inserts a new row in the model table.
         * @param Element|array $data An Element or associative array relating fields and values to insert.
         * @return mixed Returns the last inserted `AUTO_INCREMENT` value (or true) on success or false on failure.
         */
        public function create($data){
            // Clears the current built query
            $this->clearQuery();

            // Parse data and timestamps
            if($data instanceof Element) $data = $data->toArray();
            $data = $this->filterData($data);
            if($this->_timestamps){
                $data[$this->_createdField] = Kraken::raw('NOW()');
                $data[$this->_updatedField] = Kraken::raw('NOW()');
            }

            // Inserts the element
            $result = $this->insert($data);
            if($result !== false){
                return $this->lastInsertId();
            }else{
                return $result;
            }
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
            if($data instanceof Element) $data = $data->toArray();
            if(isset($data[$this->_primaryKey]) && $this->find($data[$this->_primaryKey])){
                // Parse data and timestamps
                $updatedData = $this->filterData($data);
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
            if($row instanceof Element) $row = $row->toArray();
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
            if(!empty($field)){
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
         * Refreshes the model entity back to the original filled data.\
         * **Note:** this will delete all modifications made to the model entity data.
         * @return $this Current Model instance for nested calls.
         */
        public function refresh(){
            if(!$this->_initialData) throw new Exception('refresh(): Model "' . get_class($this) . '" entity was not filled with a row data');
            return $this->fill($this->_initialData, true);
        }

        /**
         * Saves the model entity data to the database.
         * @return bool Returns true on success or false on failure.
         */
        public function save(){
            $data = $this->toArray();
            if(empty($data)) throw new Exception('save(): Model "' . get_class($this) . '" entity cannot be empty while saving');
            $id = $this->updateOrCreate($data);
            if(!is_bool($id)) $this->set($this->_primaryKey, $id);
        }

        /**
         * Deletes the database row matching the model entity primary key value.
         * @return bool Returns true on success or false on failure.
         * @throws Exception Throws an exception if the model entity primary key is not filled.
         */
        public function destroy(){
            $primary = $this->get($this->_primaryKey);
            if (is_null($primary)) throw new Exception('destroy(): Model "' . get_class($this) . '" entity primary key was not filled');
            return $this->drop($primary);
        }

        /**
         * Clones the current model entity removing the primary key and timestamp fields.
         * @return $this Returns a copy as a new instance of the current model.
         */
        public function clone(){
            $model = clone $this;
            $model->remove([$this->_primaryKey, $this->_createdField, $this->_updatedField, $this->_deletedField]);
            return $model;
        }

        /**
         * Casts data types of fields from a row or an array of rows using `$this->_casts` setting.
         * @param array|Element $data A single row as an Element or associative array or an array of rows.
         * @param bool $single (Optional) Set to `true` if working with a single row.
         * @return array|Element Returns the data with the casted fields.
         */
        private function castData($data, bool $single = false){
            // Checks if data or casts property is empty
            if(empty($data) || empty($this->_casts)) return $data;

            // Checks if is an array of rows
            if(!$single){
                $result = [];
                foreach($data as $item) $result[] = $this->castData($item, true);
                return $result;
            }

            // Converts the element to an array
            $isElement = false;
            if($data instanceof Element){
                $isElement = true;
                $data = $data->toArray();
            }

            // Performs the castings
            foreach($this->_casts as $field => $type){
                // Checks for the field
                if(isset($data[$field])){
                    $params = explode(':', $type, 2);
                    $type = strtolower($params[0]);

                    // Gets the rule
                    switch($type){
                        case 'array':
                            $data[$field] = json_decode($data[$field], true) ?? null;
                            break;

                        case 'json':
                            $json = json_decode($data[$field], true);
                            if($json){
                                $data[$field] = new Element($json);
                            }else{
                                $data[$field] = null;
                            }
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
         * Filters an array of data returning only the fields in `$this->_updatable` setting.
         * @param array $data An associative array of fields and values to filter.
         * @return array Returns the filtered array.
         */
        private function filterData(array $data){
            if(empty($data) || empty($this->_updatable)) return $data;
            return array_intersect_key($data, array_flip($this->_updatable));
        }

        /**
         * Sets fields filters in the query.
         * @param string|array $field Field name or associative array relating fields and values.
         * @param mixed $value (Optional) Value to search for.
         */
        private function filterFields($field, $value = null){
            if(is_array($field)){
                foreach($field as $key => $value){
                    if(is_array($value)){
                        $this->whereIn($key, $value);
                    }else{
                        $this->where($key, $value);
                    }
                }
            }else{
                if(is_array($value)){
                    $this->whereIn($field, $value);
                }else{
                    $this->where($field, $value);
                }
            }
        }

    }

?>