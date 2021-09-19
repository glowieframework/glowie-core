<?php
    namespace Glowie\Core\Tools;

    use Util;
    use Exception;

    /**
     * Data validator for Glowie application.
     * @category Data validation
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.0
     */
    class Validator{

         /**
         * Validation errors.
         * @var array
         */
        private $errors = [];

        /**
         * Returns an associative array with the latest validation errors.
         * @param string|int $key (Optional) Item/field key to get errors. Leave blank to get all.
         * @return array Returns an array with the fetched errors.
         */
        public function getErrors($key = null){
            if(!is_null($key)){
                return !empty($this->errors[$key]) ? $this->errors[$key] : [];
            }else{
                return $this->errors;
            }
        }

        /**
         * Validates an associative array of multiple fields with unique rules for each of them.
         * @param array $data Associative array of fields to be validated.
         * @param array $rules Associative array with validation rules for each field.
         * @param bool $bail (Optional) Stop validation of each field after first failure found.
         * @param bool $bailAll (Optional) Stop validation of all fields after first failure found.
         * @return bool Returns true if all rules passed for all fields, false otherwise.
         */
        public function validateFields(array $data, array $rules, bool $bail = false, bool $bailAll = false){
            // Loops throug field list
            $result = true;
            $errors = [];
            foreach($data as $key => $item){
                // Searches for field rule
                if(isset($rules[$key])){
                    // Validate item
                    $this->validate($item, $rules[$key], $bail);
                    if(!empty($this->errors)){
                        $errors[$key] = $this->errors;
                        $result = false;
                    }else{
                        $errors[$key] = [];
                    };
                    if ($bailAll && !$result) break;
                }
            }

            // Stores errors and returns the result
            $this->errors = $errors;
            return $result;
        }

        /**
         * Validates an array of multiple values with the same rules for all of them.
         * @param array $data Array of values to be validated.
         * @param string|array $rules Validation rules for the data. Can be a single rule or an array of rules.
         * @param bool $bail (Optional) Stop validation of each value after first failure found.
         * @param bool $bailAll (Optional) Stop validation of all values after first failure found.
         * @return bool Returns true if all rules passed for all values, false otherwise.
         */
        public function validateMultiple(array $data, $rules, bool $bail = false, bool $bailAll = false){
            // Loops through data array
            $errors = [];
            $result = true;
            foreach($data as $key => $item){
                // Validate item
                $this->validate($item, $rules, $bail);
                if (!empty($this->errors)){
                    $errors[$key] = $this->errors;
                    $result = false;
                }else{
                    $errors[$key] = [];
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

                // Check type of rule
                switch($rule[0]){

                    // [REQUIRED] - Checks if variable is not empty or null
                    case 'required':
                        if(!isset($data) || $data == []){
                            $result['required'] = true;
                        }else{
                            if(is_string($data) && trim($data) == '') $result['required'] = true;
                        }
                        break;

                    // [MIN] - Checks if variable is bigger or equal than min
                    case 'min':
                        if(is_array($data)){
                            if(count($data) < $rule[1]) $result['min'] = true;
                        }else if(is_string($data)){
                            if(strlen($data) < $rule[1]) $result['min'] = true;
                        }else{
                            if($data < $rule[1]) $result['min'] = true;
                        }
                        break;

                    // [MAX] - Checks if variable is lower or equal than max
                    case 'max':
                        if (is_array($data)) {
                            if (count($data) > $rule[1]) $result['max'] = true;
                        } else if (is_string($data)) {
                            if (strlen($data) > $rule[1]) $result['max'] = true;
                        } else {
                            if ($data > $rule[1]) $result['max'] = true;
                        }
                        break;

                    // [SIZE] - Checks if variable size equals to size
                    case 'size':
                        if (is_array($data)) {
                            if (count($data) != $rule[1]) $result['size'] = true;
                        } else if (is_string($data)) {
                            if (strlen($data) != $rule[1]) $result['size'] = true;
                        } else {
                            if ($data != $rule[1]) $result['size'] = true;
                        }
                        break;

                    // [EMAIL] - Checks if variable is a valid email
                    case 'email':
                        if(!filter_var($data, FILTER_VALIDATE_EMAIL)) $result['email'] = true;
                        break;

                    // [URL] - Checks if variable is a valid URL
                    case 'url':
                        if (!filter_var($data, FILTER_VALIDATE_URL)) $result['url'] = true;
                        break;

                    // [ALPHA] - Checks if variable is alphabetic
                    case 'alpha':
                        if(!preg_match('/^[a-z]+$/i', $data)) $result['alpha'] = true;
                        break;

                    // [NUMERIC] - Checks if variable is a number
                    case 'numeric':
                        if(!is_numeric($data)) $result['numeric'] = true;
                        break;

                    // [ALPHANUMERIC] - Checks if variable is alphanumeric
                    case 'alphanumeric':
                        if (!preg_match('/^[a-z0-9]+$/i', $data)) $result['alphanumeric'] = true;
                        break;

                    // [ALPHADASH] - Checks if variable is alphanumeric or has dashes/underscores
                    case 'alphadash':
                        if (!preg_match('/^[a-z0-9-_]+$/i', $data)) $result['alphadash'] = true;
                        break;

                    // [REGEX] - Checks if variable matches a regex pattern
                    case 'regex':
                        if(!preg_match($rule[1], $data)) $result['regex'] = true;
                        break;

                    // [ARRAY] - Checks if variable is an array
                    case 'array':
                        if(!is_array($data)) $result['array'] = true;
                        break;

                    // [DATE] - Checks if variable is a valid date
                    case 'date':
                        if (!strtotime($data)) $result['date'] = true;
                        break;

                    // [STRING] - Checks if variable is a string
                    case 'string':
                        if (!is_string($data)) $result['string'] = true;
                        break;

                    // [INTEGER] - Checks if variable is an integer
                    case 'integer':
                        if (!is_int($data)) $result['integer'] = true;
                        break;

                    // [FLOAT] - Checks if variable is a float
                    case 'float':
                        if (!is_float($data)) $result['float'] = true;
                        break;

                    // [FILE] - Checks if path is an existing file
                    case 'file':
                        if(!is_file($data)) $result['file'] = true;
                        break;

                    // [UPLOAD] - Checks if variable is an uploaded file through HTTP POST
                    case 'upload':
                        if(!is_uploaded_file($data)) $result['upload'] = true;
                        break;

                    // [DIRECTORY] - Checks if path is an existing directory
                    case 'directory':
                        if(!is_dir($data)) $result['directory'] = true;
                        break;

                    // [WRITABLE] - Checks if path is a writable directory or file
                    case 'writable':
                        if(!is_writable($data)) $result['writable'] = true;
                        break;

                    // [OBJECT] - Checks if variable is an object
                    case 'object':
                        if(!is_object($data)) $result['object'] = true;
                        break;

                    // [BOOLEAN] - Checks if variable is a boolean
                    case 'boolean':
                        if(!is_bool($data)) $result['boolean'] = true;
                        break;

                    // [JSON] - Checks if string is valid JSON format
                    case 'json':
                        if(!json_decode($data)) $result['json'] = true;
                        break;

                    // [VALUE] - Checks if variable matches value
                    case 'value':
                        if($data != $rule[1]) $result['value'] = true;
                        break;

                    // [NOT] - Checks if variable does not match value
                    case 'not':
                        if ($data == $rule[1]) $result['not'] = true;
                        break;

                    // [EMPTY] - Check if variable is empty
                    case 'empty':
                        if (is_string($data)){
                            if(trim($data) != '') $result['empty'] = true;
                        }else{
                            if (isset($data)){
                                if(!empty($data)) $result['empty'] = true;
                            }
                        }
                        break;

                    // [ENDSWITH] - Check if variable ends with string
                    case 'endswith':
                        if (Util::endsWith($data, $rule[1])) $result['endswith'] = true;
                        break;

                    // [STARTSWITH] - Check if variable starts with string
                    case 'startswith':
                        if (Util::startsWith($data, $rule[1])) $result['startswith'] = true;
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