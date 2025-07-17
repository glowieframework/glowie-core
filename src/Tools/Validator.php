<?php

namespace Glowie\Core\Tools;

use Babel;
use Util;
use Exception;
use Glowie\Core\Collection;

/**
 * Data validator for Glowie application.
 * @category Data validation
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 * @see https://glowie.gabrielsilva.dev.br/docs/latest/forms-and-data/data-validation
 */
class Validator
{

    /**
     * Validation errors.
     * @var array
     */
    private $errors = [];

    /**
     * Custom validation rules.
     * @var array
     */
    private static $custom = [];

    /**
     * Creates a new instance of the Validator in a static-binding.
     * @return Validator New instance of the Validator.
     */
    public static function make()
    {
        return new static;
    }

    /**
     * Sets a custom validation rule. This rule is persisted through all `Validator` instances.
     * @param string $rule Custom rule name.
     * @param callable $callback A validation function that receives the data as the first parameter and returns a boolean if valid.
     */
    public static function setCustomRule(string $rule, callable $callback)
    {
        self::$custom[$rule] = $callback;
    }

    /**
     * Returns a Collection with the latest validation errors.
     * @return Collection Returns a Collection with the fetched errors.
     */
    public function getErrors()
    {
        return new Collection($this->errors);
    }

    /**
     * Gets the validation error messages.
     * @param string|null $lang (Optional) Language name to get the messages from. Leave empty to use the current active language.
     * @return Collection Returns a Collection with the error messages.
     */
    public function getMessages(?string $lang = null)
    {
        $messages = [];
        foreach ($this->errors as $field => $rule) {
            if (is_array($rule)) {
                foreach ($rule as $name) {
                    $messages[$field] = Babel::get("validation.$name", ['field' => $field], $lang);
                }
            } else {
                $messages[$field] = Babel::get("validation.$rule", ['field' => $field], $lang);
            }
        }
        return new Collection($messages);
    }

    /**
     * Checks if the validation has errors.
     * @param mixed $key (Optional) Item/field key to check for errors. Leave blank to check all.
     * @param string|null $rule (Optional) Specific rule to check for errors in the item/field. Leave blank to check all.
     * @return bool Returns if the validation has the specified errors.
     */
    public function hasErrors($key = null, ?string $rule = null)
    {
        $errors = $this->getErrors();
        if (is_null($key)) return $errors->isNotEmpty();
        if (is_null($rule)) return $errors->has($key);
        return in_array($rule, $errors->get($key, []));
    }

    /**
     * Validates multiple fields with unique validation rules for each one of them.
     * @param mixed $data An Element, Collection or associative array of fields to be validated.
     * @param array $rules Associative array with validation rules for each field.
     * @param bool $bail (Optional) Stop validation of each field after first failure found.
     * @param bool $bailAll (Optional) Stop validation of all fields after first failure found.
     * @return bool Returns true if all rules passed for all fields, false otherwise.
     */
    public function validateFields($data, array $rules, bool $bail = false, bool $bailAll = false)
    {
        // Converts Element data to array
        if (is_callable([$data, 'toArray'])) $data = $data->toArray();
        if (!Util::isAssociativeArray($rules)) throw new Exception('Validator: Rules must be an associative array for each field/ruleset pairs');

        // Loops through rules
        $result = true;
        $errors = [];

        foreach ($rules as $field => $ruleset) {
            // Checks for array validation
            if (Util::stringContains($field, '.')) {
                $arrayValues = $this->extractWildcardValues($data, $field);
                if (empty($arrayValues)) continue;

                // Validate the array
                $isValid = $this->validateMultiple(array_values($arrayValues), $ruleset, $bail, $bailAll);
                if (!$isValid) {
                    // Parse the error indexes
                    $paths = array_keys($arrayValues);
                    for ($i = 0; $i < count($paths); $i++) {
                        if (!empty($this->errors[$i])) {
                            $errors[$paths[$i]] = $this->errors[$i];
                            $result = false;
                        }
                    }

                    // Stop on bail all
                    if ($bailAll) break;
                }
            } else {
                // Validate single item
                $this->validate($data[$field] ?? null, $ruleset, $bail);

                // Store validation errors
                if (!empty($this->errors)) {
                    $errors[$field] = $this->errors;
                    $result = false;
                }

                // Stop on bail all
                if ($bailAll && !$result) break;
            }
        }

        // Stores errors and returns the result
        $this->errors = $errors;
        return $result;
    }

    /**
     * Validates multiple values with the same rules for all of them.
     * @param mixed $data Array of values to be validated. You can also use an Element or Collection.
     * @param mixed $rules Validation rules for the data. Can be a single rule, an array of rules or a callable function.
     * @param bool $bail (Optional) Stop validation of each value after first failure found.
     * @param bool $bailAll (Optional) Stop validation of all values after first failure found.
     * @return bool Returns true if all rules passed for all values, false otherwise.
     */
    public function validateMultiple($data, $rules, bool $bail = false, bool $bailAll = false)
    {
        // Converts Element data to array
        if (is_callable([$data, 'toArray'])) $data = $data->toArray();

        // Get array values only
        $data = array_values($data);

        // Loops through data array
        $errors = [];
        $result = true;

        foreach ($data as $key => $item) {
            // Validate item
            $this->validate($item, $rules, $bail);
            if (!empty($this->errors)) {
                $errors[$key] = $this->errors;
                $result = false;
            };

            // Stop on bail all
            if ($bailAll && !$result) break;
        }

        // Stores errors and returns the result
        $this->errors = $errors;
        return $result;
    }

    /**
     * Validates a single value.
     * @param mixed $data Value to be validated.
     * @param mixed $rules Validation rules for the data. Can be a single rule, an array of rules or a callable function.
     * @param bool $bail (Optional) Stop validation after first failure found.
     * @return bool Returns true if all rules passed, false otherwise.
     */
    public function validate($data, $rules, bool $bail = false)
    {
        // Stores result
        $result = [];

        // Loops through rule array
        foreach ((array)$rules as $rule) {

            // Check for callable rule
            if (is_callable($rule)) {
                $cb = call_user_func_array($rule, [$data]);
                if ($cb === false) $result['callable'];

                // Stop on bail
                if ($bail && !empty($result)) break;
                continue;
            }

            // Parse rule parameters, if available
            $rule = explode(':', $rule, 2);
            $type = mb_strtolower($rule[0]);

            // Check type of rule
            switch ($type) {
                // [NULLABLE] - Checks if variable exists before checking the next rules
                case 'nullable':
                case 'optional':
                case 'sometimes':
                    if (!isset($data) || Util::isEmpty($data)) break 2;
                    break;

                // [CUSTOM] - Checks for custom rule
                case 'custom':
                    if (!isset($rule[1])) throw new Exception('Validator: Missing parameter for "custom" rule');
                    if (!self::callCustomRule($rule[1], $data)) $result[] = $rule[1];
                    break;

                // [REQUIRED] - Checks if variable is not empty or null
                case 'required':
                    if (!isset($data) || Util::isEmpty($data)) $result[] = 'required';
                    break;

                // [MIN] - Checks if variable is bigger or equal than min
                case 'min':
                    if (!isset($rule[1])) throw new Exception('Validator: Missing parameter for "min" rule');
                    $rule[1] = (int)$rule[1];
                    if (Util::getSize($data) < $rule[1]) $result[] = 'min';
                    break;

                // [MAX] - Checks if variable is lower or equal than max
                case 'max':
                    if (!isset($rule[1])) throw new Exception('Validator: Missing parameter for "max" rule');
                    $rule[1] = (int)$rule[1];
                    if (Util::getSize($data) > $rule[1]) $result[] = 'max';
                    break;

                // [SIZE] - Checks if variable size equals to size
                case 'size':
                    if (!isset($rule[1])) throw new Exception('Validator: Missing parameter for "size" rule');
                    $rule[1] = (int)$rule[1];
                    if (Util::getSize($data) != $rule[1]) $result[] = 'size';
                    break;

                // [BETWEEN] - Checks if variable size is between the specified
                case 'between':
                    if (!isset($rule[1])) throw new Exception('Validator: Missing parameter for "between" rule');
                    $rule[1] = explode(',', $rule[1], 2);
                    if (count($rule[1]) !== 2) throw new Exception('Validator: Between rule must have two values for min and max');
                    $size = Util::getSize($data);
                    if ($size < (int)$rule[1][0] || $size > (int)$rule[1][1]) $result[] = 'between';
                    break;

                // [EMAIL] - Checks if variable is a valid email
                case 'email':
                    if (!is_string($data) || !filter_var($data, FILTER_VALIDATE_EMAIL)) $result[] = 'email';
                    break;

                // [URL] - Checks if variable is a valid URL
                case 'url':
                    if (!is_string($data) || !filter_var($data, FILTER_VALIDATE_URL)) $result[] = 'url';
                    break;

                // [IP] - Checks if variable is a valid IP address
                case 'ip':
                    if (!is_string($data) || !filter_var($data, FILTER_VALIDATE_IP)) $result[] = 'ip';
                    break;

                // [MAC_ADDRESS] - Checks if variable is a valid MAC address
                case 'macaddress':
                case 'mac_address':
                    if (!is_string($data) || !filter_var($data, FILTER_VALIDATE_MAC)) $result[] = 'mac_address';
                    break;

                // [ALPHA] - Checks if variable is alphabetic
                case 'alpha':
                    if (!is_string($data) || !preg_match('/^[a-z]+$/i', $data)) $result[] = 'alpha';
                    break;

                // [NUMERIC] - Checks if variable is a number
                case 'numeric':
                    if (!is_numeric($data)) $result[] = 'numeric';
                    break;

                // [ALPHA_NUMERIC] - Checks if variable is alphanumeric
                case 'alphanumeric':
                case 'alpha_numeric':
                    if (!is_string($data) || !preg_match('/^[a-z0-9]+$/i', $data)) $result[] = 'alpha_numeric';
                    break;

                // [ALPHA_DASH] - Checks if variable is alphanumeric or has dashes/underscores
                case 'alphadash':
                case 'alpha_dash':
                    if (!is_string($data) || !preg_match('/^[a-z0-9-_]+$/i', $data)) $result[] = 'alpha_dash';
                    break;

                // [UUID] - Checks if variable is a valid universally unique identifier
                case 'uuid':
                    if (!is_string($data) || preg_match('/^\{?[0-9a-f]{8}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?[0-9a-f]{12}\}?$/i', $data) !== 1) $result[] = 'uuid';
                    break;

                // [REGEX] - Checks if variable matches a regex pattern
                case 'regex':
                    if (!isset($rule[1])) throw new Exception('Validator: Missing parameter for "regex" rule');
                    if (!is_string($data) || !preg_match($rule[1], $data)) $result[] = 'regex';
                    break;

                // [ARRAY] - Checks if variable is an array
                case 'array':
                    if (!is_array($data)) $result[] = 'array';
                    break;

                // [INDEXED] - Checks if variable is an indexed/numeric array
                case 'indexed':
                    if (Util::isAssociativeArray($data)) $result[] = 'indexed';
                    break;

                // [ASSOC] - Checks if variable is an associative array
                case 'assoc':
                    if (!Util::isAssociativeArray($data)) $result[] = 'assoc';
                    break;

                // [DATE] - Checks if variable is a valid date
                case 'date':
                    if (!is_string($data) || !strtotime($data)) $result[] = 'date';
                    break;

                // [BEFORE] - Checks if date is before a specific date
                case 'before':
                    if (!isset($rule[1])) throw new Exception('Validator: Missing parameter for "before" rule');
                    if (!is_string($data) || strtotime($data) >= strtotime($rule[1])) $result[] = 'before';
                    break;

                // [AFTER] - Checks if date is after a specific date
                case 'after':
                    if (!isset($rule[1])) throw new Exception('Validator: Missing parameter for "after" rule');
                    if (!is_string($data) || strtotime($data) <= strtotime($rule[1])) $result[] = 'after';
                    break;

                // [STRING] - Checks if variable is a string
                case 'string':
                    if (!is_string($data)) $result[] = 'string';
                    break;

                // [INTEGER] - Checks if variable is an integer
                case 'integer':
                    if (!is_int($data)) $result[] = 'integer';
                    break;

                // [FLOAT] - Checks if variable is a float
                case 'float':
                    if (!is_float($data)) $result[] = 'float';
                    break;

                // [FILE] - Checks if path is an existing file
                case 'file':
                    if (!is_string($data) || !is_file($data)) $result[] = 'file';
                    break;

                // [NOT_FILE] - Check if path is not an existing file
                case 'notfile':
                case 'not_file':
                    if (is_string($data) && is_file($data)) $result[] = 'not_file';
                    break;

                // [UPLOAD] - Checks if variable is an uploaded file through HTTP POST
                case 'upload':
                    if (!is_string($data) || !is_uploaded_file($data)) $result[] = 'upload';
                    break;

                // [DIRECTORY] - Checks if path is an existing directory
                case 'directory':
                    if (!is_string($data) || !is_dir($data)) $result[] = 'directory';
                    break;

                // [NOT_DIRECTORY] - Checks if path is not an existing directory
                case 'notdirectory':
                case 'not_directory':
                    if (is_string($data) && !is_dir($data)) $result[] = 'not_directory';
                    break;

                // [MIME] - Checks if file matches a list of mime types
                case 'mime':
                    if (!isset($rule[1])) throw new Exception('Validator: Missing parameter for "mime" rule');
                    if (!is_string($data) || !is_file($data) || !in_array(mime_content_type($data), explode(',', $rule[1]))) $result[] = 'mime';
                    break;

                // [NOT_MIME] - Checks if file is not a list of mime types
                case 'notmime':
                case 'not_mime':
                    if (!isset($rule[1])) throw new Exception('Validator: Missing parameter for "notmime" rule');
                    if (!is_string($data) || !is_file($data) || in_array(mime_content_type($data), explode(',', $rule[1]))) $result[] = 'not_mime';
                    break;

                // [IN] - Checks if string matches a list of values (loose comparison)
                case 'in':
                    if (!isset($rule[1])) throw new Exception('Validator: Missing parameter for "in" rule');
                    if (!is_string($data) || !in_array(trim($data), explode(',', $rule[1]))) $result[] = 'in';
                    break;

                // [IN_STRICT] - Checks if string matches a list of values (strict comparison)
                case 'instrict':
                case 'in_strict':
                    if (!isset($rule[1])) throw new Exception('Validator: Missing parameter for "in_strict" rule');
                    if (!is_string($data) || !in_array(trim($data), explode(',', $rule[1]), true)) $result[] = 'in_strict';
                    break;

                // [NOT_IN] - Checks if string is not in a list of values (loose comparison)
                case 'notin':
                case 'not_in':
                    if (!isset($rule[1])) throw new Exception('Validator: Missing parameter for "not_in" rule');
                    if (!is_string($data) || in_array(trim($data), explode(',', $rule[1]))) $result[] = 'not_in';
                    break;

                // [NOT_IN_STRICT] - Checks if string is not in a list of values (strict comparison)
                case 'notinstrict':
                case 'not_in_strict':
                    if (!isset($rule[1])) throw new Exception('Validator: Missing parameter for "not_in_strict" rule');
                    if (!is_string($data) || in_array(trim($data), explode(',', $rule[1]), true)) $result[] = 'not_in_strict';
                    break;

                // [WRITABLE] - Checks if path is a writable directory or file
                case 'writable':
                    if (!is_string($data) || !is_writable($data)) $result[] = 'writable';
                    break;

                // [OBJECT] - Checks if variable is an object
                case 'object':
                    if (!is_object($data)) $result[] = 'object';
                    break;

                // [BOOLEAN] - Checks if variable is a boolean
                case 'boolean':
                    if (!is_bool($data)) $result[] = 'boolean';
                    break;

                // [TRUE] - Checks if variable is a truthy value
                case 'true':
                    if (!filter_var($data, FILTER_VALIDATE_BOOLEAN)) $result[] = 'true';
                    break;

                // [FALSE] - Checks if variable is a falsy value
                case 'false':
                    if (filter_var($data, FILTER_VALIDATE_BOOLEAN)) $result[] = 'true';
                    break;

                // [JSON] - Checks if string is valid JSON format
                case 'json':
                    if (!Util::isJson($data)) $result[] = 'json';
                    break;

                // [VALUE] - Checks if variable matches value (loose comparison)
                case 'value':
                    if (!isset($rule[1])) throw new Exception('Validator: Missing parameter for "value" rule');
                    if ($data != $rule[1]) $result[] = 'value';
                    break;

                // [VALUE] - Checks if variable matches value (strict comparison)
                case 'valuestrict':
                case 'value_strict':
                    if (!isset($rule[1])) throw new Exception('Validator: Missing parameter for "value_strict" rule');
                    if ($data !== $rule[1]) $result[] = 'value_strict';
                    break;

                // [NOT] - Checks if variable does not match value (loose comparison)
                case 'not':
                    if (!isset($rule[1])) throw new Exception('Validator: Missing parameter for "not" rule');
                    if ($data == $rule[1]) $result[] = 'not';
                    break;

                // [NOT_STRICT] Checks if variable does not match value (strict comparison)
                case 'notstrict':
                case 'not_strict':
                    if (!isset($rule[1])) throw new Exception('Validator: Missing parameter for "not_strict" rule');
                    if ($data === $rule[1]) $result[] = 'not_strict';
                    break;

                // [EMPTY] - Check if variable is empty
                case 'empty':
                    if (isset($data) || !Util::isEmpty($data)) $result[] = 'empty';
                    break;

                // [ENDS_WITH] - Check if variable ends with string
                case 'endswith':
                case 'ends_with':
                    if (!isset($rule[1])) throw new Exception('Validator: Missing parameter for "ends_with" rule');
                    if (!is_string($data) || Util::endsWith($data, $rule[1])) $result[] = 'ends_with';
                    break;

                // [STARTS_WITH] - Check if variable starts with string
                case 'startswith':
                case 'starts_with':
                    if (!isset($rule[1])) throw new Exception('Validator: Missing parameter for "starts_with" rule');
                    if (!is_string($data) || Util::startsWith($data, $rule[1])) $result[] = 'starts_with';
                    break;

                // Fallback to custom rules
                default:
                    if (!self::callCustomRule($type, $data, $rule[1] ?? null)) $result[] = $type;
                    break;
            }

            // Stop on bail
            if ($bail && !empty($result)) break;
        }

        // Stores and returns the result
        $this->errors = $result;
        return empty($result) ? true : false;
    }

    /**
     * Calls a custom rule from the Validator instance or from a callable.
     * @param string $rule Custom rule name or callable (e.g. `MyValidator::validate`).
     * @param mixed $data Data to be validated.
     * @param string|null $params (Optional) Optional parameters to pass into the validation method.
     * @return boolean Returns the validation result.
     */
    private static function callCustomRule(string $rule, $data, ?string $params = null)
    {
        $callback =  self::$custom[$rule] ?? $rule;
        if (!is_callable($callback)) throw new Exception('Validator: Rule "' . $rule . '" does not exist');
        return call_user_func_array($callback, [$data, $params]);
    }

    /**
     * Extracts values from a multi-dimensional array using dot notation with wildcards.
     * @param array $data Source array to extract the values from.
     * @param string $path Dot notation path with optional wildcards.
     * @return array Returns an associative array with full dot paths as keys and the corresponding extracted values.
     */
    private function extractWildcardValues(array $data, string $path): array
    {
        $segments = explode('.', $path);
        $results = [];
        $this->collectWildcardValues($data, $segments, $results);
        return $results;
    }

    /**
     * Collects values from a multi-dimensional array by resolving wildcard segments.
     * @param mixed $data Current level of the array to traverse.
     * @param array $segments Remaining path segments to resolve.
     * @param array $results Reference to the array that stores matched keys and values.
     * @param array $currentPath (Optional) Current path segments resolved so far.
     */
    private function collectWildcardValues($data, array $segments, array &$results, array $currentPath = [])
    {
        // Converts Element data to array
        if (is_callable([$data, 'toArray'])) $data = $data->toArray();
        if (!is_array($data)) return;

        // Gets the next segment
        $segment = array_shift($segments);

        // Checks for wildcard segment
        if ($segment === '*') {
            // Loops through the data to get values
            foreach ($data as $index => $item) {
                // Creates the path
                $newPath = array_merge($currentPath, [$index]);

                // If last segment, store the value directly
                if (empty($segments)) {
                    $results[implode('.', $newPath)] = $item;
                    continue;
                }

                // Get the next segments
                $this->collectWildcardValues($item, $segments, $results, $newPath);
            }

            return;
        }

        // Gets the next value
        $newPath = array_merge($currentPath, [$segment]);

        // Checks if the value exists
        if (isset($data[$segment])) {
            // If last segment, store the value directly
            if (empty($segments)) {
                $results[implode('.', $newPath)] = $data[$segment];
                return;
            }

            // Get the next segments
            $this->collectWildcardValues($data[$segment], $segments, $results, $newPath);
        } else {
            // If last segment, store null
            if (empty($segments)) {
                $results[implode('.', $newPath)] = null;
                return;
            }

            // Get the next segments
            $this->collectWildcardValues([], $segments, $results, $newPath);
        }
    }
}
