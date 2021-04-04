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
         * Creates a new instance of this model.
         */
        public function __construct(){
            Kraken::__construct($this->table);
        }

        /**
         * Finds the first row that matches the model primary key value.
         * @param mixed $primary Primary key value to search for.
         * @return Element Returns the row on success or false on errors.
         */
        public function find($primary){
            return $this->select($this->fields)->where($this->primaryKey, $primary)->limit(1)->fetchRow();
        }

        /**
         * Gets all rows from the table.
         * @return Element[] Returns an array with all rows on success or false on errors.
         */
        public function all(){
            return $this->select($this->fields)->fetchAll();
        }

    }

?>