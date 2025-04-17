<?php

namespace Glowie\Core;

use ArrayAccess;
use Countable;
use JsonSerializable;
use Closure;
use Glowie\Core\Tools\Validator;
use Iterator;
use Util;

/**
 * Generic array instance for Glowie application.
 * @category Array
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 */
class Collection implements ArrayAccess, JsonSerializable, Iterator, Countable
{

    /**
     * Collection data.
     * @var array
     */
    private $__data = [];

    /**
     * Creates a new collection.
     * @param array $data (Optional) Initial data to parse into the Collection.
     */
    public function __construct(array $data = [])
    {
        if (!empty($data)) $this->__data = $data;
    }

    /**
     * Creates a new Collection in a static-binding.
     * @param array $data (Optional) Initial data to parse into the Collection.
     * @return Collection New Collection instance.
     */
    public static function make(array $data = [])
    {
        return new self($data);
    }

    /**
     * Gets the value associated to a key in the Collection data.
     * @param string|int $key Key to get value (accepts dot notation keys).
     * @param mixed $default (Optional) Default value to return if the key does not exist.
     * @return mixed Returns the value if exists or the default if not.
     */
    public function get($key, $default = null)
    {
        return Util::arrayGet($this->__data, $key, $default);
    }

    /**
     * Sets the value for a key in the Collection data.
     * @param mixed $key Key to set value (accepts dot notation keys). You can also pass an array\
     * of values (or another Collection) to set at once and they will be merged into the Collection data.
     * @param mixed $value (Optional) Value to set.
     * @param bool $ignoreDot (Optional) Ignore dot notation keys.
     * @return Collection Current Collection instance for nested calls.
     */
    public function set($key, $value = null, bool $ignoreDot = false)
    {
        if (is_array($key) || $key instanceof Collection) {
            if ($key instanceof Collection) $key = $key->toArray();
            $this->__data = array_merge($this->__data, $key);
        } else {
            if ($ignoreDot) {
                $this->__data[$key] = $value;
            } else {
                Util::arraySet($this->__data, $key, $value);
            }
        }
        return $this;
    }

    /**
     * Merges two Collections or arrays.
     * @param Collection|array A Collection or array to merge with the current Collection.
     * @return Collection Returns a new Collection with the merged data.
     */
    public function merge($data)
    {
        if ($data instanceof Collection) $data = $data->toArray();
        return new Collection(array_merge($this->__data, $data));
    }

    /**
     * Shuffle the Collection data.
     * @return Collection Returns a new Collection with shuffled data.
     */
    public function shuffle()
    {
        $arr = $this->__data;
        shuffle($arr);
        return new Collection($arr);
    }

    /**
     * Removes the associated key value from the Collection data.
     * @param mixed $key Key to delete value (accepts dot notation keys). You can also use an array of keys to remove.
     * @param bool $ignoreDot (Optional) Ignore dot notation keys.
     * @return Collection Current Collection instance for nested calls.
     */
    public function remove($key, bool $ignoreDot = false)
    {
        if (is_array($key)) {
            foreach ($key as $item) $this->remove($item, $ignoreDot);
        } else {
            if (!$ignoreDot) {
                Util::arrayDelete($this->__data, $key);
            } else {
                unset($this->__data[$key]);
            }
        }
        return $this;
    }

    /**
     * Removes all Collection data, except the one that matches the specified key.
     * @param mixed $key Key to keep. You can also use an array of keys to keep.
     * @return Collection Current Collection instance for nested calls.
     */
    public function only($key)
    {
        $this->__data = array_intersect_key($this->__data, array_flip((array)$key));
        return $this;
    }

    /**
     * Checks if any value has been associated to a key in the Collection data.
     * @param mixed $key Key to check (accepts dot notation keys). You can also use an array of keys.
     * @param bool $all (Optional) Checks the presence of all items, instead of any.
     * @return bool Returns true or false.
     */
    public function has($key, bool $all = false)
    {
        $result = false;
        foreach ((array)$key as $item) {
            if (!$all && $result) break;
            $result = Util::arrayGet($this->__data, $item) !== null;
        }
        return $result;
    }

    /**
     * Checks if a value has been associated to all keys in the Collection data.
     * @param mixed $key Key to check (accepts dot notation keys). You can also use an array of keys.
     * @return bool Returns true or false.
     */
    public function hasAll($key)
    {
        return $this->has($key, true);
    }

    /**
     * Deletes all data from the current Collection.
     * @return Collection Current Collection instance for nested calls.
     */
    public function flush()
    {
        $this->__data = [];
        return $this;
    }

    /**
     * Gets the Collection data as an associative array.
     * @return array The resulting array.
     */
    public function toArray()
    {
        return $this->__data;
    }

    /**
     * Returns the serializable JSON data for the Collection.
     * @return array Collection data as an associative array.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Gets the Collection data as JSON.
     * @param int $flags (Optional) JSON encoding flags (same as in `json_encode()` function).
     * @param int $depth (Optional) JSON encoding maximum depth (same as in `json_encode()` function).
     * @return string The resulting JSON string.
     */
    public function toJson(int $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES, int $depth = 512)
    {
        return json_encode($this->__data, $flags, $depth);
    }

    /**
     * Transforms the Collection into an Element.
     * @return Element Element with the Collection data.
     */
    public function toElement()
    {
        return new Element($this->__data);
    }

    /**
     * Dumps the Collection data.
     * @param bool $plain (Optional) Dump data as plain text instead of HTML.
     */
    public function dump(bool $plain = false)
    {
        Util::dump($this, $plain);
    }

    /**
     * Gets the Collection data as a string (data will be serialized as JSON).
     * @return string The resulting JSON string.
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * Collection debugging information.
     */
    public function __debugInfo()
    {
        return $this->__data;
    }

    /**
     * Gets the first value from the Collection.
     * @param mixed $default (Optional) Default value to return if Collection is empty.
     * @return mixed Returns the value or the default if empty.
     */
    public function first($default = null)
    {
        return reset($this->__data) ?? $default;
    }

    /**
     * Gets the first key from the Collection.
     * @param mixed $default (Optional) Default value to return if Collection is empty.
     * @return mixed Returns the key or the default if empty.
     */
    public function firstKey($default = null)
    {
        $this->first($default);
        return key($this->__data) ?? $default;
    }

    /**
     * Gets the last value from the Collection.
     * @param mixed $default (Optional) Default value to return if Collection is empty.
     * @return mixed Returns the value or the default if empty.
     */
    public function last($default = null)
    {
        return end($this->__data) ?? $default;
    }

    /**
     * Gets the last key from the Collection.
     * @param mixed $default (Optional) Default value to return if Collection is empty.
     * @return mixed Returns the key or the default if empty.
     */
    public function lastKey($default = null)
    {
        $this->last($default);
        return key($this->__data) ?? $default;
    }

    /**
     * Searches the value in the Collection and returns its key if found, false otherwise.
     * @param mixed $value Value to search.
     * @param bool $strict (Optional) Use type checking when searching.
     * @return mixed Returns the key or false if not found.
     */
    public function searchKey($value, bool $strict = false)
    {
        return array_search($value, $this->__data, $strict);
    }

    /**
     * Pushes an item to the end of the Collection.
     * @param mixed $value Value to push.
     * @return Collection Current Collection instance for nested calls.
     */
    public function push($value)
    {
        $this->__data[] = $value;
        return $this;
    }

    /**
     * Shift an element off the beginning of the Collection.
     */
    public function shift()
    {
        return array_shift($this->__data);
    }

    /**
     * Pushes an item to the beggining of the Collection.
     * @param mixed $value Value to push.
     * @return Collection Current Collection instance for nested calls.
     */
    public function unshift($value)
    {
        array_unshift($this->__data, $value);
        return $this;
    }

    /**
     * Pops the last element of the Collection and return its value.
     * @return mixed Popped element value.
     */
    public function pop()
    {
        return array_pop($this->__data);
    }

    /**
     * Sorts the Collection values.
     * @param int $order (Optional) Sort direction `SORT_ASC` (ascending) or `SORT_DESC` (descending).
     * @param bool $preserveKeys (Optional) Keep keys association.
     * @return Collection Returns a new Collection with the sorted data.
     */
    public function sort(int $order = SORT_ASC, bool $preserveKeys = false)
    {
        $arr = $this->__data;

        if ($preserveKeys) {
            if ($order === SORT_ASC) {
                asort($arr);
            } else {
                arsort($arr);
            }
        } else {
            if ($order === SORT_ASC) {
                sort($arr);
            } else {
                rsort($arr);
            }
        }

        return new Collection($arr);
    }

    /**
     * Sorts the Collection by keys.
     * @param int $order (Optional) Sort direction `SORT_ASC` (ascending) or `SORT_DESC` (descending).
     * @return Collection Returns a new Collection with the sorted data.
     */
    public function sortKeys(int $order = SORT_ASC)
    {
        $arr = $this->__data;

        if ($order === SORT_ASC) {
            ksort($arr);
        } else {
            krsort($arr);
        }

        return new Collection($arr);
    }

    /**
     * Reverse the Collection data order.
     * @param bool $preserveKeys (Optional) Keep keys association.
     * @return Collection Returns a new Collection with reversed data.
     */
    public function reverse(bool $preserveKeys = false)
    {
        return new Collection(array_reverse($this->__data, $preserveKeys));
    }

    /**
     * Reorders the Collection by a key value.
     * @param mixed $key Key to use as the reordering base.
     * @param int $order (Optional) Ordering direction: `SORT_ASC` (ascending) or `SORT_DESC` (descending).
     * @return Collection Returns a new Collection with the ordered data.
     */
    public function orderBy($key, int $order = SORT_ASC)
    {
        return new Collection(Util::orderArray($this->__data, $key, $order));
    }

    /**
     * Filters the Collection with a truth test.
     * @param Closure $callback Function to check in the data, receives the value and key of each item.
     * @return Collection Returns a new Collection with the filtered data.
     */
    public function filter(Closure $callback)
    {
        return new Collection(array_filter($this->__data, $callback, ARRAY_FILTER_USE_BOTH));
    }

    /**
     * Filters the Collection leaving only items that match a key value.
     * @param mixed $key Key to use as the filtering base. You can also use an associative array with keys and values to match.
     * @param mixed $value (Optional) Value to filter if using a single key.
     * @param bool $strict (Optional) Use strict types while comparing values.
     * @return Collection Returns a new Collection with the filtered data.
     */
    public function filterBy($key, $value = null, bool $strict = false)
    {
        return new Collection(Util::filterArray($this->__data, $key, $value, $strict));
    }

    /**
     * Changes the case of all keys in the Collection.
     * @param int $case (Optional) Either `CASE_UPPER` or `CASE_LOWER`.
     * @return Collection Returns a new Collection with the changed keys.
     */
    public function changeKeyCase(int $case = CASE_LOWER)
    {
        return new Collection(array_change_key_case($this->__data, $case));
    }

    /**
     * Return the values of a single column in the Collection data.
     * @param mixed $key The column of values to return.
     * @param mixed $index (Optional) The column to use as the index key in the returned Collection.
     * @return Collection Returns a new Collection with the selected column.
     */
    public function column($key, $index = null)
    {
        return new Collection(array_column($this->__data, $key, $index));
    }

    /**
     * Returns the keys from the Collection.
     * @return Collection Returns a new Collection with the keys.
     */
    public function keys()
    {
        return new Collection(array_keys($this->__data));
    }

    /**
     * Returns the values from the Collection.
     * @return Collection Returns a new Collection with the values.
     */
    public function values()
    {
        return new Collection(array_values($this->__data));
    }

    /**
     * Searches the Collection for the first item that matches a key value.
     * @param mixed $key Key to match value.
     * @param mixed $value Value to search.
     * @return array Returns the first item found.
     */
    public function search($key, $value)
    {
        return Util::searchArray($this->__data, $key, $value);
    }

    /**
     * Checks if the Collection contains a value.
     * @param mixed $value Value to search for.
     * @param bool $strict (Optional) Use type checking.
     * @return bool Returns if the Collection contains the value or not.
     */
    public function contains($value, bool $strict = false)
    {
        return in_array($value, $this->__data, $strict);
    }

    /**
     * Checks if the Collection contains a value with type checking.
     * @param mixed $value Value to search for.
     * @return bool Returns if the Collection contains the value or not.
     */
    public function containsStrict($value)
    {
        return $this->contains($value, true);
    }

    /**
     * Flattens the Collection into a new single level Collection with dot notation.
     * @return Collection Returns a new flattened Collection.
     */
    public function flatten()
    {
        return new Collection(Util::dotArray($this->__data));
    }

    /**
     * Removes duplicate values from the Collection.
     * @return Collection Returns a new unique Collection.
     */
    public function unique()
    {
        return new Collection(array_unique($this->__data));
    }

    /**
     * Removes duplicate values from the Collection matching a key value.
     * @param mixed $key Key to check.
     * @return Collection Returns a new unique Collection.
     */
    public function uniqueBy($key)
    {
        $result = [];
        $i = 0;
        $keys = [];
        foreach ($this->__data as $val) {
            if (!in_array($val[$key], $keys)) {
                $keys[$i] = $val[$key];
                $result[$i] = $val;
            }
            $i++;
        }
        return new Collection($result);
    }

    /**
     * Groups the Collection using a key value.
     * @param mixed $key Key to use as grouping base.
     * @return Collection Returns a new grouped Collection.
     */
    public function groupBy($key)
    {
        $result = [];
        foreach ($this->__data as $val) {
            if (is_array($val)) {
                $result[$val[$key]][] = $val;
            } else {
                $result[$val->{$key}][] = $val;
            }
        }
        return new Collection($result);
    }

    /**
     * Combines two Collections into a new associative Collection.
     * @param Collection $values A Collection of values to be combined with the current Collection keys.
     * @return Collection Returns a new Collection.
     */
    public function combine(Collection $values)
    {
        $values = $values->toArray();
        return new Collection(array_combine($this->__data, $values));
    }

    /**
     * Gets the difference between two Collections using only its values.
     * @param Collection $compare Collection to be compared with.
     * @param Closure $callback (Optional) Custom comparison function callback.
     * @return Collection Returns a new Collection containing the difference.
     */
    public function diff(Collection $compare, ?Closure $callback)
    {
        $compare = $compare->toArray();
        if ($callback) {
            return new Collection(array_udiff($this->__data, $compare, $callback));
        } else {
            return new Collection(array_diff($this->__data, $compare));
        }
    }

    /**
     * Gets the difference between two Collections using its keys and values.
     * @param Collection $compare Collection to be compared with.
     * @param Closure $callback (Optional) Custom comparison function callback.
     * @return Collection Returns a new Collection containing the difference.
     */
    public function diffAssoc(Collection $compare, ?Closure $callback)
    {
        $compare = $compare->toArray();
        if ($callback) {
            return new Collection(array_diff_uassoc($this->__data, $compare, $callback));
        } else {
            return new Collection(array_diff_assoc($this->__data, $compare));
        }
    }

    /**
     * Gets the difference between two Collections using only its keys.
     * @param Collection $compare Collection to be compared with.
     * @param Closure $callback (Optional) Custom comparison function callback.
     * @return Collection Returns a new Collection containing the difference.
     */
    public function diffKeys(Collection $compare, ?Closure $callback)
    {
        $compare = $compare->toArray();
        if ($callback) {
            return new Collection(array_diff_ukey($this->__data, $compare, $callback));
        } else {
            return new Collection(array_diff_key($this->__data, $compare));
        }
    }

    /**
     * Extracts a portion of items from the Collection.
     * @param int $offset Starting index.
     * @param int|null $length (Optional) Slice length.
     * @param bool $preserveKeys (Optional) Keep current keys in slice.
     * @return Collection Returns a new Collection.
     */
    public function slice(int $offset, ?int $length, bool $preserveKeys = false)
    {
        return new Collection(array_slice($this->__data, $offset, $length, $preserveKeys));
    }

    /**
     * Extracts a portion of items from the start of the Collection.\
     * If the number is negative, extracts from the end of the Collection.
     * @param int $length Slice length.
     * @param bool $preserveKeys (Optional) Keep current keys in slice.
     * @return Collection Returns a new Collection.
     */
    public function take(int $length, bool $preserveKeys = false)
    {
        return $this->slice(0, $length, $preserveKeys);
    }

    /**
     * Unflattens the Collection from dot notation to a multi-dimensional Collection.
     * @return Collection Returns a new unflattened Collection.
     */
    public function unflatten()
    {
        return new Collection(Util::undotArray($this->__data));
    }

    /**
     * Checks if the Collection is associative rather than numerically indexed.
     * @return bool Returns true if is an associative Collection.
     */
    public function isAssociative()
    {
        return Util::isAssociativeArray($this->__data);
    }

    /**
     * Checks if the Collection is multidimensional.
     * @return bool Returns true if is a multidimensional Collection.
     */
    public function isMultidimensional()
    {
        return Util::isMultidimensionalArray($this->__data);
    }

    /**
     * Gets a random item from the Collection.
     * @return mixed Returns a random item.
     */
    public function random()
    {
        return Util::randomArray($this->__data);
    }

    /**
     * Returns the Collection as a paginated instance.
     * @param int $currentPage (Optional) Current page to get results.
     * @param int $resultsPerPage (Optional) Number of results to get per page.
     * @param int|null $range (Optional) Pagination range interval (for `pages` array).
     * @return Element Returns an Element with the pagination result.
     */
    public function paginate(int $currentPage = 1, int $resultsPerPage = 25, ?int $range = null)
    {
        $result = Util::paginateArray($this->__data, $currentPage, $resultsPerPage, $range);
        $result->data = new Collection($result->data);
        $result->pages = new Collection($result->pages);
        return $result;
    }

    /**
     * Splits the Collection into smaller chunks.
     * @param int $length Size of each chunk.
     * @param bool $preserveKeys (Optional) Keep keys association.
     * @return Collection Returns a multidimensional Collection of chunks.
     */
    public function chunk(int $length, bool $preserveKeys = false)
    {
        $result = array_chunk($this->__data, $length, $preserveKeys);
        foreach ($result as &$arr) $arr = new Collection($arr);
        return new Collection($result);
    }

    /**
     * Iterates through each item of the Collection. This mutates the original Collection values.
     * @param Closure $callback Function to be called. Returning `false` will stop the iteration.
     * @return Collection Current Collection instance for nested calls.
     */
    public function each(Closure $callback)
    {
        foreach ($this->__data as $key => &$value) {
            $result = $callback($value, $key);
            if ($result === false) break;
        }
        return $this;
    }

    /**
     * Applies a function to all items of the Collection. This does not mutate the original Collection.
     * @param Closure $callback Function to be called.
     * @return Collection Returns a new Collection with the new data.
     */
    public function map(Closure $callback)
    {
        $data = array_map($callback, $this->__data);
        return new Collection($data);
    }

    /**
     * Checks if any item in the Collection passes a truth test.
     * @param Closure $callback Function to be tested, should return a boolean.
     * @return bool Returns the result of the test.
     */
    public function some(Closure $callback)
    {
        $result = false;
        foreach ($this->__data as $key => $value) {
            $result = $callback($value, $key);
            if ($result === true) break;
        }
        return $result;
    }

    /**
     * Returns the Collection data as a string joined with a separator.
     * @param string $separator Separator to use to "glue" the data.
     * @return string Returns the imploded data.
     */
    public function implode(string $separator)
    {
        return implode($separator, $this->__data);
    }

    /**
     * Sums the values of the Collection.
     * @return int|float Returns the sum.
     */
    public function sum()
    {
        return array_sum($this->__data);
    }

    /**
     * Checks if current Collection is empty.
     * @return bool True or false for empty Collection.
     */
    public function isEmpty()
    {
        return empty($this->__data);
    }

    /**
     * Checks if current Collection is not empty.
     * @return bool True or false for not empty Collection.
     */
    public function isNotEmpty()
    {
        return !empty($this->__data);
    }

    /**
     * Checks if any value has been associated to a key in the Collection data.
     * @param string|int $offset Key to check (accepts dot notation keys).
     * @return bool Returns true or false.
     */
    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    /**
     * Gets the value associated to a key in the Collection data.
     * @param string|int $offset Key to get value (accepts dot notation keys).
     * @return mixed Returns the value if exists or null if not.
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Sets the value for a key in the Collection data.
     * @param mixed $offset Key to set value (accepts dot notation keys).
     * @param mixed $value (Optional) Value to set.
     */
    public function offsetSet($offset, $value): void
    {
        $this->set($offset, $value);
    }

    /**
     * Removes the associated key value from the Collection data.
     * @param mixed $offset Key to delete value.
     */
    public function offsetUnset($offset): void
    {
        $this->remove($offset);
    }

    /**
     * Returns the current item from the Collection.
     * @return mixed Current item.
     */
    public function current()
    {
        return current($this->__data);
    }

    /**
     * Returns the key of the current item from the Collection.
     * @return mixed Current key.
     */
    public function key()
    {
        return key($this->__data);
    }

    /**
     * Moves to the next item of the Collection.
     */
    public function next(): void
    {
        next($this->__data);
    }

    /**
     * Goes back to the previous item of the Collection.
     */
    public function rewind(): void
    {
        reset($this->__data);
    }

    /**
     * Checks if current item from the Collection is valid.
     * @return bool True or false.
     */
    public function valid(): bool
    {
        return $this->key() !== null;
    }

    /**
     * Returns the size of the Collection.
     * @return int Collection size.
     */
    public function count(): int
    {
        return count($this->__data);
    }

    /**
     * Validates the Collection data using unique validation rules for each one of the fields.
     * @param array $rules Associative array with validation rules for each field.
     * @param bool $bail (Optional) Stop validation of each field after first failure found.
     * @param bool $bailAll (Optional) Stop validation of all fields after first failure found.
     * @return bool Returns true if all rules passed for all fields, false otherwise.
     */
    public function validate(array $rules, bool $bail = false, bool $bailAll = false)
    {
        return (new Validator())->validateFields($this->toArray(), $rules, $bail, $bailAll);
    }

    /**
     * Validates the Collection data using the same rules for all values.
     * @param string|array $rules Validation rules for the data. Can be a single rule or an array of rules.
     * @param bool $bail (Optional) Stop validation of each value after first failure found.
     * @param bool $bailAll (Optional) Stop validation of all values after first failure found.
     * @return bool Returns true if all rules passed for all values, false otherwise.
     */
    public function validateAll($rules, bool $bail = false, bool $bailAll = false)
    {
        return (new Validator())->validateMultiple($this->toArray(), $rules, $bail, $bailAll);
    }
}
