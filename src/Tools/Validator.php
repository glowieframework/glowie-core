<?php
    namespace Glowie\Core\Tools;

    use Util;
    use Exception;
    use Countable;
    use Closure;

    /**
     * Data validator for Glowie application.
     * @category Data validation
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) Glowie
     * @license MIT
     * @link https://eugabrielsilva.tk/glowie
     */
    class Validator{

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
         * Sets a custom validation rule. This rule is persisted through all `Validator` instances.
         * @param string $rule Custom rule name.
         * @param Closure $callback A validation function that receives the data as the first parameter and returns a boolean if valid.
         */
        public static function setCustomRule(string $rule, Closure $callback){
            self::$custom[$rule] = $callback;
        }

        /**
         * Returns an associative array with the latest validation errors.
         * @param mixed $key (Optional) Item/field key to get errors. Leave blank to get all.
         * @return array Returns an array with the fetched errors.
         */
        public function getErrors($key = null){
            if(is_null($key)) return $this->errors;
            return $this->errors[$key] ?? [];
        }

        /**
         * Checks if the validation has errors.
         * @param mixed $key (Optional) Item/field key to check for errors. Leave blank to check all.
         * @param string|null $rule (Optional) Specific rule to check for errors in the item/field. Leave blank to check all.
         * @return bool Returns if the validation has the specified errors.
         */
        public function hasError($key = null, ?string $rule = null){
            if(is_null($key)) return !empty($this->errors);
            if(is_null($rule)) return isset($this->errors[$key]);
            if(!isset($this->errors[$key])) return false;
            return in_array($rule, $this->errors[$key]);
        }

        /**
         * Validates multiple fields with unique validation rules for each one of them.
         * @param array|Element $data An Element or associative array of fields to be validated.
         * @param array $rules Associative array with validation rules for each field.
         * @param bool $bail (Optional) Stop validation of each field after first failure found.
         * @param bool $bailAll (Optional) Stop validation of all fields after first failure found.
         * @return bool Returns true if all rules passed for all fields, false otherwise.
         */
        public function validateFields($data, array $rules, bool $bail = false, bool $bailAll = false){
            // Converts Element data to array
            if(is_callable([$data, 'toArray'])) $data = $data->toArray();
            if(!Util::isAssociativeArray($rules)) throw new Exception('Validator: Rules must be an associative array for each field/ruleset pairs');

            // Loops through rules
            $result = true;
            $errors = [];
            foreach($rules as $field => $ruleset){
                // Validate item
                $this->validate($data[$field] ?? null, $ruleset, $bail);
                if(!empty($this->errors)){
                    $errors[$field] = $this->errors;
                    $result = false;
                }
                if($bailAll && !$result) break;
            }

            // Stores errors and returns the result
            $this->errors = $errors;
            return $result;
        }

        /**
         * Validates multiple values with the same rules for all of them.
         * @param array|Element $data Array of values to be validated. You can also use an Element.
         * @param string|array $rules Validation rules for the data. Can be a single rule or an array of rules.
         * @param bool $bail (Optional) Stop validation of each value after first failure found.
         * @param bool $bailAll (Optional) Stop validation of all values after first failure found.
         * @return bool Returns true if all rules passed for all values, false otherwise.
         */
        public function validateMultiple($data, $rules, bool $bail = false, bool $bailAll = false){
            // Converts Element data to array
            if(is_callable([$data, 'toArray'])) $data = $data->toArray();

            // Get array values only
            $data = array_values($data);

            // Loops through data array
            $errors = [];
            $result = true;
            foreach($data as $key => $item){
                // Validate item
                $this->validate($item, $rules, $bail);
                if (!empty($this->errors)){
                    $errors[$key] = $this->errors;
                    $result = false;
                };
                if ($bailAll && !$result) break;
            }

            // Stores errors and returns the result
            $this->errors = $errors;
            return $result;
        }

        /**
         * Validates a single value.
         * @param mixed $data Value to be validated.
         * @param string|array $rules Validation rules for the data. Can be a single rule or an array of rules.
         * @param bool $bail (Optional) Stop validation after first failure found.
         * @return bool Returns true if all rules passed, false otherwise.
         */
        public function validate($data, $rules, bool $bail = false){
            // Stores result
            $result = [];

            // Loops through rule array
            foreach((array)$rules as $rule){

                // Parse rule parameters, if available
                $rule = explode(':', $rule, 2);
                $type = strtolower($rule[0]);

                // Check type of rule
                switch($type){
                    // [CUSTOM] - Checks for custom rule
                    case 'custom':
                        if(!isset($rule[1])) throw new Exception('Validator: Missing parameter for "custom" rule');
                        if(empty(self::$custom[$rule[1]])) throw new Exception('Validator: Custom rule "' . $rule[1] . '" does not exist');
                        if(!self::$custom[$rule[1]]($data)) $result[] = $rule[1];
                        break;

                    // [REQUIRED] - Checks if variable is not empty or null
                    case 'required':
                        if (!isset($data) || Util::isEmpty($data)) $result[] = 'required';
                        break;

                    // [MIN] - Checks if variable is bigger or equal than min
                    case 'min':
                        if(!isset($rule[1])) throw new Exception('Validator: Missing parameter for "min" rule');
                        $rule[1] = (int)$rule[1];
                        if($data instanceof Countable){
                            if(count($data) < $rule[1]) $result[] = 'min';
                        }else if(is_string($data) && is_file($data)){
                            if(filesize($data) < ($rule[1] * 1024)) $result[] = 'min';
                        }else if(is_string($data)){
                            if(mb_strlen($data) < $rule[1]) $result[] = 'min';
                        }else if(is_numeric($data)){
                            if($data < $rule[1]) $result[] = 'min';
                        }else{
                            $result[] = 'min';
                        }
                        break;

                    // [MAX] - Checks if variable is lower or equal than max
                    case 'max':
                        if(!isset($rule[1])) throw new Exception('Validator: Missing parameter for "max" rule');
                        $rule[1] = (int)$rule[1];
                        if ($data instanceof Countable) {
                            if (count($data) > $rule[1]) $result[] = 'max';
                        } else if(is_string($data) && is_file($data)){
                            if(filesize($data) > ($rule[1] * 1024)) $result[] = 'max';
                        } else if (is_string($data)) {
                            if (mb_strlen($data) > $rule[1]) $result[] = 'max';
                        } else if(is_numeric($data)) {
                            if ($data > $rule[1]) $result[] = 'max';
                        }else{
                            $result[] = 'max';
                        }
                        break;

                    // [SIZE] - Checks if variable size equals to size
                    case 'size':
                        if(!isset($rule[1])) throw new Exception('Validator: Missing parameter for "size" rule');
                        $rule[1] = (int)$rule[1];
                        if ($data instanceof Countable) {
                            if (count($data) != $rule[1]) $result[] = 'size';
                        } else if(is_string($data) && is_file($data)){
                            if(filesize($data) != ($rule[1] * 1024)) $result[] = 'size';
                        } else if (is_string($data)) {
                            if (mb_strlen($data) != $rule[1]) $result[] = 'size';
                        } else if(is_numeric($data)){
                            if ($data != $rule[1]) $result[] = 'size';
                        } else {
                            $result[] = 'size';
                        }
                        break;

                    // [EMAIL] - Checks if variable is a valid email
                    case 'email':
                        if(!is_string($data)){
                            $result[] = 'email';
                        }else{
                            if(!filter_var($data, FILTER_VALIDATE_EMAIL)) $result[] = 'email';
                        }
                        break;

                        // [URL] - Checks if variable is a valid URL
                    case 'url':
                        if(!is_string($data)){
                            $result[] = 'url';
                        }else{
                            if (!filter_var($data, FILTER_VALIDATE_URL)) $result[] = 'url';
                        }
                        break;

                    // [ALPHA] - Checks if variable is alphabetic
                    case 'alpha':
                        if(!is_string($data)){
                            $result[] = 'alpha';
                        }else{
                            if(!preg_match('/^[a-z]+$/i', $data)) $result[] = 'alpha';
                        }
                        break;

                    // [NUMERIC] - Checks if variable is a number
                    case 'numeric':
                        if(!is_numeric($data)) $result[] = 'numeric';
                        break;

                    // [ALPHANUMERIC] - Checks if variable is alphanumeric
                    case 'alphanumeric':
                        if(!is_string($data)){
                            $result[] = 'alphanumeric';
                        }else{
                            if (!preg_match('/^[a-z0-9]+$/i', $data)) $result[] = 'alphanumeric';
                        }
                        break;

                    // [ALPHADASH] - Checks if variable is alphanumeric or has dashes/underscores
                    case 'alphadash':
                        if(!is_string($data)){
                            $result[] = 'alphadash';
                        }else{
                            if (!preg_match('/^[a-z0-9-_]+$/i', $data)) $result[] = 'alphadash';
                        }
                        break;

                    // [UUID] - Checks if variable is a valid universally unique identifier
                    case 'uuid':
                        if(!is_string($data)){
                            $result[] = 'uuid';
                        }else{
                            if (preg_match('/^\{?[0-9a-f]{8}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?[0-9a-f]{12}\}?$/i', $data) !== 1) $result[] = 'uuid';
                        }
                        break;

                    // [REGEX] - Checks if variable matches a regex pattern
                    case 'regex':
                        if(!isset($rule[1])) throw new Exception('Validator: Missing parameter for "regex" rule');
                        if(!is_string($data)){
                            $result[] = 'regex';
                        }else{
                            if(!preg_match($rule[1], $data)) $result[] = 'regex';
                        }
                        break;

                    // [ARRAY] - Checks if variable is an array
                    case 'array':
                        if(!is_array($data)) $result[] = 'array';
                        break;

                    // [ASSOC] - Checks if variable is an associative array
                    case 'assoc':
                        if(!Util::isAssociativeArray($data)) $result[] = 'assoc';
                        break;

                    // [DATE] - Checks if variable is a valid date
                    case 'date':
                        if(!is_string($data)){
                            $result[] = 'date';
                        }else{
                            if (!strtotime($data)) $result[] = 'date';
                        }
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
                        if (!is_string($data)){
                            $result[] = 'file';
                        }else{
                            if(!is_file($data)) $result[] = 'file';
                        }
                        break;

                    // [UPLOAD] - Checks if variable is an uploaded file through HTTP POST
                    case 'upload':
                        if(!is_string($data)){
                            $result[] = 'upload';
                        }else{
                            if(!is_uploaded_file($data)) $result[] = 'upload';
                        }
                        break;

                    // [DIRECTORY] - Checks if path is an existing directory
                    case 'directory':
                        if(!is_string($data)){
                            $result[] = 'directory';
                        }else{
                            if(!is_dir($data)) $result[] = 'directory';
                        }
                        break;

                    // [MIME] - Checks if file matches a list of mime types
                    case 'mime':
                        if(!isset($rule[1])) throw new Exception('Validator: Missing parameter for "mime" rule');
                        if(!is_string($data) || !is_file($data)){
                            $result[] = 'mime';
                        }else{
                            if(!in_array(mime_content_type($data), explode(',', $rule[1]))) $result[] = 'mime';
                        }
                        break;

                    // [WRITABLE] - Checks if path is a writable directory or file
                    case 'writable':
                        if(!is_string($data)){
                            $result[] = 'writable';
                        }else{
                            if(!is_writable($data)) $result[] = 'writable';
                        }
                        break;

                    // [OBJECT] - Checks if variable is an object
                    case 'object':
                        if(!is_object($data)) $result[] = 'object';
                        break;

                    // [BOOLEAN] - Checks if variable is a boolean
                    case 'boolean':
                        if(!is_bool($data)) $result[] = 'boolean';
                        break;

                    // [TRUE] - Checks if variable is a truthy value
                    case 'true':
                        if(!filter_var($data, FILTER_VALIDATE_BOOLEAN)) $result[] = 'true';
                        break;

                    // [FALSE] - Checks if variable is a falsy value
                    case 'false':
                        if(filter_var($data, FILTER_VALIDATE_BOOLEAN)) $result[] = 'true';
                        break;

                    // [JSON] - Checks if string is valid JSON format
                    case 'json':
                        if(!Util::isJson($data)) $result[] = 'json';
                        break;

                    // [VALUE] - Checks if variable matches value
                    case 'value':
                        if(!isset($rule[1])) throw new Exception('Validator: Missing parameter for "value" rule');
                        if($data != $rule[1]) $result[] = 'value';
                        break;

                    // [NOT] - Checks if variable does not match value
                    case 'not':
                        if(!isset($rule[1])) throw new Exception('Validator: Missing parameter for "not" rule');
                        if ($data == $rule[1]) $result[] = 'not';
                        break;

                    // [EMPTY] - Check if variable is empty
                    case 'empty':
                        if (isset($data) || !Util::isEmpty($data)) $result[] = 'empty';
                        break;

                    // [ENDSWITH] - Check if variable ends with string
                    case 'endswith':
                        if(!isset($rule[1])) throw new Exception('Validator: Missing parameter for "endswith" rule');
                        if (Util::endsWith($data, $rule[1])) $result[] = 'endswith';
                        break;

                    // [STARTSWITH] - Check if variable starts with string
                    case 'startswith':
                        if(!isset($rule[1])) throw new Exception('Validator: Missing parameter for "startswith" rule');
                        if (Util::startsWith($data, $rule[1])) $result[] = 'startswith';
                        break;
                }

                if($bail && !empty($result)) break;
            }

            // Stores and returns the result
            $this->errors = $result;
            return empty($result) ? true : false;
        }

    }

?>