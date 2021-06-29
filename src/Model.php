<?php
    namespace Glowie\Core;

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
        
        /**
         * Model table name.
         * @var string
         */
        protected $_table = 'glowie';

        /**
         * Table primary key name.
         * @var string
         */
        protected $_primaryKey = 'id';

        /**
         * Table manageable fields.
         * @var string[]
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
         * @var Element
         */
        private $_initialData = null;

        /**
         * Creates a new instance of the model.
         */
        public function __construct(){
            Kraken::__construct($this->_table);
        }

        /**
         * Gets the first row that matches the model primary key value.
         * @param mixed $primary Primary key value to search for.
         * @return Element|null Returns the row on success or null if not found.
         */
        public function find($primary){
            $this->clearQuery();
            $fields = !empty($this->_fields) ? $this->_fields : '*';
            return $this->select($fields)->where($this->_primaryKey, $primary)->fetchRow();
        }
        
        /**
         * Gets all rows from the model table.
         * @param string $order (Optional) Ordering direction for the primary key field **(ASC or DESC)**.
         * @return array Returns an array with all rows.
         */
        public function all(){
            $this->clearQuery();
            $fields = !empty($this->_fields) ? $this->_fields : '*';
            return $this->select($fields)->fetchAll();
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
         * @param Element $row Row object to retrieve data.
         */
        public function fill(Element $row){
            $this->_initialData = $row;
            Element::__construct($row->toArray());
        }

        /**
         * Checks if the row data has been modified in the model entity.
         * @param mixed $field (Optional) Field to check. Leave empty to compare everything.
         * @return bool Returns true if the row data has been modified.
         */
        public function isDirty($field = ''){
            if(!$this->_initialData instanceof Element) trigger_error('isDirty: Model entity was not filled with a row data', E_USER_ERROR);
            if(!empty($field)){
                return ($this->_initialData->get($field) !== $this->get($field));
            }else{
                return ($this->_initialData->toArray() !== $this->toArray());
            }
        }

        /**
         * Checks if the row data has not been modified in the model entity.
         * @param mixed $field (Optional) Field to check. Leave empty to compare everything.
         * @return bool Returns true if the row data has not been modified.
         */
        public function isPristine($field = ''){
            if(!$this->_initialData instanceof Element) trigger_error('isPristine: Model entity was not filled with a row data', E_USER_ERROR);
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