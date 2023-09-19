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

        /**
         * Creates a new collection.
         * @param array $data Initial data to parse into the Collection.
         */
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

        /**
         * Gets the first value from the Collection.
         * @param mixed $default (Optional) Default value to return if Collection is empty.
         * @return mixed Returns the value or the default if empty.
         */
        public function first($default = null){
            return reset($this->__data) ?? $default;
        }

        /**
         * Gets the first key from the Collection.
         * @param mixed $default (Optional) Default value to return if Collection is empty.
         * @return mixed Returns the key or the default if empty.
         */
        public function firstKey($default = null){
            $this->first($default);
            return key($this->__data) ?? $default;
        }

        /**
         * Gets the last value from the Collection.
         * @param mixed $default (Optional) Default value to return if Collection is empty.
         * @return mixed Returns the value or the default if empty.
         */
        public function last($default = null){
            return end($this->__data) ?? $default;
        }

        /**
         * Gets the last key from the Collection.
         * @param mixed $default (Optional) Default value to return if Collection is empty.
         * @return mixed Returns the key or the default if empty.
         */
        public function lastKey($default = null){
            $this->last($default);
            return key($this->__data) ?? $default;
        }

        /**
         * Pushes an item to the end of the Collection.
         * @param mixed $value Value to push.
         * @return Collection Current Collection instance for nested calls.
         */
        public function push($value){
            $this->__data[] = $value;
            return $this;
        }

        /**
         * Sorts the Collection values.
         * @param int $order (Optional) Sort direction `SORT_ASC` (ascending) or `SORT_DESC` (descending).
         * @param bool $preserveKeys (Optional) Keep keys association.
         * @return Collection Returns a new Collection with the sorted data.
         */
        public function sort(int $order = SORT_ASC, bool $preserveKeys = false){
            $arr = $this->__data;

            if($preserveKeys){
                if($order == SORT_ASC){
                    asort($arr);
                }else{
                    arsort($arr);
                }
            }else{
                if($order == SORT_ASC){
                    sort($arr);
                }else{
                    rsort($arr);
                }
            }

            return new Collection($arr);
        }

        /**
         * Reorders the Collection by a key value.
         * @param mixed $key Key to use as the reordering base.
         * @param int $order (Optional) Ordering direction: `SORT_ASC` (ascending) or `SORT_DESC` (descending).
         * @return Collection Returns a new Collection with the ordered data.
         */
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

        public function isMultidimensional(){
            return Util::isMultidimensionalArray($this->__data);
        }

        /**
         * Gets a random item from the Collection.
         * @return mixed Returns a random item.
         */
        public function random(){
            return Util::randomArray($this->__data);
        }

        /**
         * Returns the Collection as a paginated instance.
         * @param int $currentPage (Optional) Current page to get results.
         * @param int $resultsPerPage (Optional) Number of results to get per page.
         * @param int|null $range (Optional) Pagination range interval (for `pages` array).
         * @return Element Returns an Element with the pagination result.
         */
        public function paginate(int $currentPage = 1, int $resultsPerPage = 25, ?int $range = null){
            return Util::paginateArray($this->__data, $currentPage, $resultsPerPage, $range);
        }

        /**
         * Checks if any value has been associated to a key in the Collection data.
         * @param string|int $offset Key to check (accepts dot notation keys).
         * @return bool Returns true or false.
         */
        public function offsetExists($offset): bool{
            return $this->has($offset);
        }

        /**
         * Gets the value associated to a key in the Collection data.
         * @param string|int $offset Key to get value (accepts dot notation keys).
         * @return mixed Returns the value if exists or null if not.
         */
        public function offsetGet($offset){
            return $this->get($offset);
        }

        /**
         * Sets the value for a key in the Collection data.
         * @param mixed $offset Key to set value (accepts dot notation keys).
         * @param mixed $value (Optional) Value to set.
         */
        public function offsetSet($offset, $value): void{
            $this->set($offset, $value);
        }

        /**
         * Removes the associated key value from the Collection data.
         * @param mixed $offset Key to delete value.
         */
        public function offsetUnset($offset): void{
            $this->remove($offset);
        }

        /**
         * Returns the current item from the Collection.
         * @return mixed Current item.
         */
        public function current(){
            return current($this->__data);
        }

        /**
         * Returns the key of the current item from the Collection.
         * @return mixed Current key.
         */
        public function key(){
            return key($this->__data);
        }

        /**
         * Moves to the next item of the Collection.
         */
        public function next(): void{
            next($this->__data);
        }

        /**
         * Goes back to the previous item of the Collection.
         */
        public function rewind(): void{
            reset($this->__data);
        }

        /**
         * Checks if current item from the Collection is valid.
         * @return bool True or false.
         */
        public function valid(): bool{
            return $this->key() !== null;
        }

        /**
         * Returns the size of the Collection.
         * @return int Collection size.
         */
        public function count(): int{
            return count($this->__data);
        }

    }

?>