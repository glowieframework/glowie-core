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
     * @version 0.3-alpha
     */
    class Model extends Kraken{
        
        /**
         * Model table name.
         * @var string
         */
        protected $table = 'glowie';

        /**
         * Table primary key name.
         * @var string
         */
        protected $primaryKey = 'id';

        /**
         * Table manageable fields.
         * @var string[]
         */
        protected $fields = [];

        /**
         * Handle timestamp fields.
         * @var bool
         */
        protected $timestamps = false;

        /**
         * Creates a new instance of the model.
         */
        public function __construct(){
            Kraken::__construct($this->table);
        }

        /**
         * Gets the first row that matches the model primary key value.
         * @param mixed $primary Primary key value to search for.
         * @return Element Returns the row on success or false on errors.
         */
        public function find($primary){
            $this->clearQuery();
            $fields = !empty($this->fields) ? $this->fields : '*';
            return $this->select($fields)->where($this->primaryKey, $primary)->limit(1)->fetchRow();
        }
        
        /**
         * Gets all rows from the model table.
         * @return Element[] Returns an array with all rows on success or false on errors.
         */
        public function all(){
            $this->clearQuery();
            $fields = !empty($this->fields) ? $this->fields : '*';
            return $this->select($fields)->fetchAll();
        }

        /**
         * Inserts a new row in the model table.
         * @param array $data An associative array relating fields and values to insert.
         * @return bool Returns true on success or false on errors.
         */
        public function create(array $data){
            // Clears the current built query
            $this->clearQuery();

            // Parse data and timestamps
            $data = $this->filterData($data);
            if($this->timestamps){
                $data['created_at'] = Kraken::raw('NOW()');
                $data['updated_at'] = Kraken::raw('NOW()');
            }

            // Inserts the element
            return $this->insert($data);
        }

        /**
         * Checks if a row matches the primary key value in the data. If so, updates the row. Otherwise,\
         * inserts a new record in the model table.
         * @param array $data An associative array relating fields and values to upsert. **Must include the primary key field.**
         * @return bool Returns true on success or false on errors.
         */
        public function updateOrCreate(array $data){
            // Clears the current built query
            $this->clearQuery();
            
            // Checks if the primary key was passed and matches an existing row
            if(isset($data[$this->primaryKey]) && $this->where($this->primaryKey, $data[$this->primaryKey])->exists()){
                // Parse data and timestamps
                $updatedData = $this->filterData($data);
                if($this->timestamps) $updatedData['updated_at'] = Kraken::raw('NOW()');
                
                // Updates the element
                return $this->where($this->primaryKey, $data[$this->primaryKey])->update($updatedData);
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
            $this->_data = $row->toArray();
        }

        public function save(){
            return $this->updateOrCreate($this->toArray());
        }

        /**
         * Filters a data array returning only the specified fields in `$this->fields` property.
         * @param array $data An associative array of fields and values to filter.
         * @return array Returns the filtered array.
         */
        private function filterData(array $data){
            if(empty($this->fields)) return $data;
            $result = [];
            foreach($data as $key => $value){
                if(in_array($key, $this->fields)) $result[$key] = $value;
            }
            return $result;
        }

    }

?>