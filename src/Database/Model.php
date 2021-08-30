<?php
    namespace Glowie\Core\Database;

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
     * @version 1.0
     */
    class Model extends Kraken{
        use ElementTrait;

        /**
         * Model table name.
         * @var string
         */
        protected $_table = '';

        /**
         * Table primary key name.
         * @var string
         */
        protected $_primaryKey = 'id';

        /**
         * Table manageable fields.
         * @var array
         */
        protected $_fields = [];

        /**
         * Handle timestamp fields.
         * @var bool
         */
        protected $_timestamps = false;

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
         * The initial data from a filled row.
         * @var Element|null
         */
        private $_initialData = null;

        /**
         * Creates a new instance of the model.
         */
        final public function __construct(){
            // Gets the table name
            if(empty($this->_table)){
                $classname = explode('\\', get_class($this));
                $this->_table = Util::snakeCase(end($classname));
            }

            // Constructs the query builder
            Kraken::__construct($this->_table);
        }

        /**
         * Gets the first row that matches the model primary key value.
         * @param mixed $primary Primary key value to search for.
         * @param bool $assoc (Optional) Return the result as an associative array.
         * @return mixed Returns the row on success or null if not found.
         */
        public function find($primary, bool $assoc = false){
            $this->clearQuery();
            $fields = !empty($this->_fields) ? $this->_fields : '*';
            return $this->select($fields)->where($this->_primaryKey, $primary)->limit(1)->fetchRow($assoc);
        }

        /**
         * Gets the first row that matches a field value.
         * @param string $field Field to use while searching.
         * @param mixed $value Value to search for.
         * @param bool $assoc (Optional) Return the result as an associative array.
         * @return mixed Returns the row on success or null if not found.
         */
        public function findBy(string $field, $value, bool $assoc = false){
            $this->clearQuery();
            $fields = !empty($this->_fields) ? $this->_fields : '*';
            return $this->select($fields)->where($field, $value)->limit(1)->fetchRow($assoc);
        }

        /**
         * Gets all rows from the model table.
         * @param bool $assoc (Optional) Return each result as an associative array.
         * @return array Returns an array with all rows.
         */
        public function all(bool $assoc = false){
            $this->clearQuery();
            $fields = !empty($this->_fields) ? $this->_fields : '*';
            return $this->select($fields)->fetchAll($assoc);
        }

        /**
         * Gets all rows from the model table ordering by the newest **created at** field.
         * @param bool $assoc (Optional) Return each result as an associative array.
         * @return array Returns an array with all rows.
         */
        public function latest(bool $assoc = false){
            if(!$this->_timestamps) throw new Exception('latest(): This model is not handling timestamp fields');
            $this->clearQuery();
            $fields = !empty($this->_fields) ? $this->_fields : '*';
            return $this->select($fields)->orderBy($this->_createdField, 'DESC')->fetchAll($assoc);
        }

        /**
         * Gets all rows from the model table ordering by the oldest **created at** field.
         * @param bool $assoc (Optional) Return each result as an associative array.
         * @return array Returns an array with all rows.
         */
        public function oldest(bool $assoc = false){
            if(!$this->_timestamps) throw new Exception('oldest(): This model is not handling timestamp fields');
            $this->clearQuery();
            $fields = !empty($this->_fields) ? $this->_fields : '*';
            return $this->select($fields)->orderBy($this->_createdField, 'ASC')->fetchAll($assoc);
        }

        /**
         * Deletes the first row that matches the model primary key value.
         * @param mixed $primary Primary key value to search for.
         * @return bool Returns true on success.
         */
        public function drop($primary){
            $this->clearQuery();
            return $this->where($this->_primaryKey, $primary)->delete();
        }

        /**
         * Inserts a new row in the model table.
         * @param array $data An associative array relating fields and values to insert.
         * @return bool Returns true on success.
         */
        public function create(array $data){
            // Clears the current built query
            $this->clearQuery();

            // Parse data and timestamps
            $data = $this->filterData($data);
            if($this->_timestamps){
                $data[$this->_createdField] = Kraken::raw('NOW()');
                $data[$this->_updatedField] = Kraken::raw('NOW()');
            }

            // Inserts the element
            return $this->insert($data);
        }

        /**
         * Checks if a row matches the primary key value in the data. If so, updates the row. Otherwise,\
         * inserts a new record in the model table.
         * @param array $data An associative array relating fields and values to upsert. **Must include the primary key field.**
         * @return bool Returns true on success.
         */
        public function updateOrCreate(array $data){
            // Clears the current built query
            $this->clearQuery();

            // Checks if the primary key was passed and matches an existing row
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
         * Fills the model entity with a row data.
         * @param Element|array $row Row to retrieve data.
         * @return Model Current Model instance for nested calls.
         */
        public function fill($row){
            if(is_array($row)) $row = new Element($row);
            $this->_initialData = $row;
            $this->__constructTrait($row->toArray());
            return $this;
        }

        /**
         * Checks if the row data has been modified in the model entity.
         * @param string $field (Optional) Field to check. Leave empty to compare everything.
         * @return bool Returns true if the row data has been modified or false otherwise.
         */
        public function isDirty(string $field = ''){
            if(!$this->_initialData instanceof Element) throw new Exception('isDirty(): Model entity was not filled with a row data');
            if(!empty($field)){
                return ($this->_initialData->get($field) !== $this->get($field));
            }else{
                return ($this->_initialData->toArray() !== $this->toArray());
            }
        }

        /**
         * Checks if the row data has not been modified in the model entity.
         * @param string $field (Optional) Field to check. Leave empty to compare everything.
         * @return bool Returns true if the row data has not been modified or false otherwise.
         */
        public function isPristine(string $field = ''){
            if(!$this->_initialData instanceof Element) throw new Exception('isPristine(): Model entity was not filled with a row data');
            return !$this->isDirty($field);
        }

        /**
         * Saves the model entity data to a row using `updateOrCreate()` method.
         * @return bool Returns true on success.
         */
        public function save(){
            return $this->updateOrCreate($this->toArray());
        }

        /**
         * Filters a data array returning only the specified fields in `$this->_fields` property.
         * @param array $data An associative array of fields and values to filter.
         * @return array Returns the filtered array.
         */
        private function filterData(array $data){
            if(empty($this->_fields)) return $data;
            $result = [];
            foreach($data as $key => $value){
                if(in_array($key, $this->_fields)) $result[$key] = $value;
            }
            return $result;
        }

    }

?>