<?php
    namespace Glowie\Core;

    use ArrayAccess;
    use Countable;
    use JsonSerializable;
    use Iterator;
    use Util;

    /**
     * Generic array instance for Glowie application.
     * @category Array
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) Glowie
     * @license MIT
     * @link https://eugabrielsilva.tk/glowie
     */
    class Collection implements ArrayAccess, JsonSerializable, Iterator, Countable{

        /**
         * Collection data.
         * @var array
         */
        private $__data = [];

        public function __construct(array $data = []){
            if(!empty($data)) $this->__data = $data;
        }

        /**
         * Gets the value associated to a key in the Collection data.
         * @param string|int $key Key to get value (accepts dot notation keys).
         * @param mixed $default (Optional) Default value to return if the key does not exist.
         * @return mixed Returns the value if exists or the default if not.
         */
        public function get($key, $default = null){
            return Util::arrayGet($this->__data, $key, $default);
        }

        /**
         * Sets the value for a key in the Collection data.
         * @param mixed $key Key to set value (accepts dot notation keys). You can also pass an array\
         * of values to set at once and they will be merged into the Collection data.
         * @param mixed $value (Optional) Value to set.
         * @param bool $ignoreDot (Optional) Ignore dot notation keys.
         * @return Collection Current Collection instance for nested calls.
         */
        public function set($key, $value = null, bool $ignoreDot = false){
            if(is_array($key)){
                $this->__data = array_merge($this->__data, $key);
            }else{
                if($ignoreDot){
                    $this->__data[$key] = $value;
                }else{
                    Util::arraySet($this->__data, $key, $value);
                }
            }
            return $this;
        }

        /**
         * Removes the associated key value from the Collection data.
         * @param mixed $key Key to delete value. You can also use an array of keys to remove.
         * @return Collection Current Collection instance for nested calls.
         */
        public function remove($key){
            foreach((array)$key as $item){
                if (isset($this->__data[$item])) unset($this->__data[$item]);
            }
            return $this;
        }

        /**
         * Removes all Collection data, except the one that matches the specified key.
         * @param mixed $key Key to keep. You can also use an array of keys to keep.
         * @return Collection Current Collection instance for nested calls.
         */
        public function only($key){
            foreach($this->__data as $field => $value){
                if(!in_array($field, (array)$key)) $this->remove($field);
            }
            return $this;
        }

        /**
         * Checks if any value has been associated to a key in the Collection data.
         * @param mixed $key Key to check (accepts dot notation keys). You can also use an array of keys.
         * @return bool Returns true or false.
         */
        public function has($key){
            $result = false;
            foreach((array)$key as $item){
                if($result) break;
                $result = Util::arrayGet($this->__data, $item) !== null;
            }
            return $result;
        }

        /**
         * Deletes all data from the current Collection.
         * @return Collection Current Collection instance for nested calls.
         */
        public function flush(){
            $this->__data = [];
            return $this;
        }

        /**
         * Gets the Collection data as an associative array.
         * @return array The resulting array.
         */
        public function toArray(){
            return $this->__data;
        }

        /**
         * Returns the serializable JSON data for the Collection.
         * @return array Collection data as an associative array.
         */
        public function jsonSerialize(){
            return $this->toArray();
        }

        /**
         * Gets the Collection data as JSON.
         * @param int $flags (Optional) JSON encoding flags (same as in `json_encode()` function).
         * @param int $depth (Optional) JSON encoding maximum depth (same as in `json_encode()` function).
         * @return string The resulting JSON string.
         */
        public function toJson(int $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK, int $depth = 512){
            return json_encode($this->__data, $flags, $depth);
        }

        /**
         * Dumps the Collection data.
         * @param bool $plain (Optional) Dump data as plain text instead of HTML.
         */
        public function dump(bool $plain = false){
            Util::dump($this, $plain);
        }

        /**
         * Gets the Collection data as a string (data will be serialized as JSON).
         * @return string The resulting JSON string.
         */
        public function __toString(){
            return $this->toJson();
        }

        /**
         * Collection debugging information.
         */
        public function __debugInfo(){
            return $this->__data;
        }

        public function first($default = null){
            return reset($this->__data) ?? $default;
        }

        public function firstKey($default = null){
            $this->first($default);
            return key($this->__data) ?? $default;
        }

        public function last($default = null){
            return end($this->__data) ?? $default;
        }

        public function lastKey($default = null){
            $this->last($default);
            return key($this->__data) ?? $default;
        }

        public function push($value){
            $this->__data[] = $value;
            return $this;
        }

        public function orderBy($key, int $order = SORT_ASC){
            return new Collection(Util::orderArray($this->__data, $key, $order));
        }

        public function filterBy($key, $value = null, bool $strict = false){
            return new Collection(Util::filterArray($this->__data, $key, $value, $strict));
        }

        public function search($key, $value){
            return new Collection(Util::searchArray($this->__data, $key, $value));
        }

        public function flatten(){
            return new Collection(Util::dotArray($this->__data));
        }

        public function unflatten(){
            return new Collection(Util::undotArray($this->__data));
        }

        public function isAssociative(){
            return Util::isAssociativeArray($this->__data);
        }

        public function random(){
            return new Collection(Util::randomArray($this->__data));
        }

        public function paginate(){
            return Util::paginateArray($this->__data);
        }

        public function offsetExists($key): bool{
            return $this->has($key);
        }

        public function offsetGet($key){
            return $this->get($key);
        }

        public function offsetSet($key, $value): void{
            $this->set($key, $value);
        }

        public function offsetUnset($key): void{
            $this->remove($key);
        }

        public function current(){
            return current($this->__data);
        }

        public function key(){
            return key($this->__data);
        }

        public function next(): void{
            next($this->__data);
        }

        public function rewind(): void{
            reset($this->__data);
        }

        public function valid(): bool{
            return $this->key() !== null;
        }

        public function count(): int{
            return count($this->__data);
        }

    }

?>