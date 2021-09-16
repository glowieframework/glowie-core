<?php
    namespace Glowie\Core;

    use ArrayAccess;
    use Countable;
    use Iterator;

    /**
     * Generic safe array instance for Glowie application.
     * @category Array
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.0
     */
    class Collection implements ArrayAccess, Countable, Iterator{

        private $_data = [];

        /**
         * Creates a new Collection.
         * @param array $data (Optional) An array with the initial data to parse.
         */
        public function __construct(array $data = []){
            if(!empty($data)) $this->_data = $data;
        }

        public function offsetSet($offset, $value){
            if(is_null($offset)){
                $this->_data[] = $value;
            }else{
                $this->_data[$offset] = $value;
            }
        }

        public function offsetExists($offset){
            return isset($this->_data[$offset]);
        }

        public function offsetUnset($offset){
            if(isset($this->_data[$offset])){
                unset($this->_data[$offset]);
            }
        }

        public function offsetGet($offset){
            return $this->_data[$offset] ?? null;
        }

        public function count(){
            return count($this->_data);
        }

        public function rewind(){
            reset($this->_data);
        }

        public function current(){
            return current($this->_data);
        }

        public function key(){
            return key($this->_data);
        }

        public function next(){
            next($this->_data);
        }

        public function valid(){
            return key($this->_data) !== null;
        }

        /**
         * Gets the Collection data as JSON.
         * @param int $flags (Optional) JSON encoding flags (same as in `json_encode()` function).
         * @param int $depth (Optional) JSON encoding maximum depth (same as in `json_encode()` function).
         * @return string The resulting JSON string.
         */
        public function toJson(int $flags = 0, int $depth = 512){
            return json_encode($this->_data, $flags, $depth);
        }

        /**
         * Gets the Collection data as JSON.
         * @return string The resulting JSON string.
         */
        public function __toString(){
            return $this->toJson();
        }

    }

?>