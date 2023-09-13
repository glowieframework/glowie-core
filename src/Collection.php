<?php
    namespace Glowie\Core;

    use Glowie\Core\Traits\ElementTrait;
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
        use ElementTrait;

        public function __construct(array $data = []){
            $this->__constructTrait($data);
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