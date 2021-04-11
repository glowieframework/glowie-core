<?php
    namespace Glowie\Core;

    use Util;

    /**
     * Data validator for Glowie application.
     * @category Data validation
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 0.3-alpha
     */
    class Validator{
        
         /**
         * Validation errors.
         * @var array
         */
        private $errors = [];

        /**
         * Returns an associative array with the latest validation errors.
         * @param string|int $key (Optional) Element/field key to get errors. Leave blank to get all.
         * @return array Array with the fetched errors.
         */
        public function getErrors($key = null){
            if($key !== null){
                return !empty($this->errors[$key]) ? $this->errors[$key] : [];
            }else{
                return $this->errors;
            }
        }

        /**
         * Validates an associative array of multiple fields with unique rules for each of them.
         * @param array $data Associative array of fields to be validated.
         * @param array $rules Associative array with validation rules for each field (check docs to see valid rules).
         * @param bool $bail (Optional) Stop validation of field after first failure found.
         * @param bool $bailAll (Optional) Stop validation of all fields after first field failure found.
         * @return bool Validation result.
         */
        public function validateFields(array $data, array $rules, bool $bail = false, bool $bailAll = false){
            // Check data
            if (!is_array($data)) {
                trigger_error('validateFields: $data must be an array of fields');
            }

            // Check ruleset
            if (!is_array($rules)) {
                trigger_error('validateFields: $rules must be an array of rules');
            }

            // Loops throug field list
            $result = true;
            $errors = [];
            foreach($data as $key => $item){
                // Searches for field rule
                if(isset($rules[$key])){
                    if(!is_array($rules[$key])){
                        trigger_error('validateFields: [' . $key . '] must be an array of rules');
                    }

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
         * Validates an array of multiple elements with the same rules.
         * @param array $data Array of elements to be validated.
         * @param array $rules Validation rules (check docs to see valid rules).
         * @param bool $bail (Optional) Stop validation of element after first failure found.
         * @param bool $bailAll (Optional) Stop validation of all elements after first element failure found.
         * @return bool Validation result.
         */
        public function validateMultiple(array $data, array $rules, bool $bail = false, bool $bailAll = false){
            // Check data
            if(!is_array($data)){
                trigger_error('validateMultiple: $data must be an array of elements');
            }

            // Check ruleset
            if (!is_array($rules)) {
                trigger_error('validateMultiple: $rules must be an array of rules');
            }

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
         * Validates a single variable.
         * @param mixed $data Data to be validated.
         * @param array $rules Validation rules (check docs to see valid rules).
         * @param bool $bail (Optional) Stop validation after first failure found.
         * @return bool Validation result.
         */
        public function validate($data, array $rules, bool $bail = false){
            // Check ruleset
            if (!is_array($rules)) {
                trigger_error('validate: $rules must be an array of rules');
            }
            
            $result = [];

            // Loops through rule array
            foreach($rules as $rule){

                // Parse rule parameters, if available
                $rule = explode(':', $rule, 2);
                
                // Check type of rule
                switch($rule[0]){

                    // [REQUIRED] - Checks if variable is not empty or null
                    case 'required':
                        if(!isset($data) || empty($data)){
                            $result['required'] = 'INVALID';
                        }else{
                            if(is_string($data) && trim($data) == '') $result['required'] = 'INVALID';
                        }
                        break;
                    
                    // [MIN] - Checks if variable is bigger or equal than min
                    case 'min':
                        if(is_array($data)){
                            if(count($data) < $rule[1]) $result['min'] = 'INVALID';
                        }else if(is_string($data)){
                            if(strlen($data) < $rule[1]) $result['min'] = 'INVALID';
                        }else{
                            if($data < $rule[1]) $result['min'] = 'INVALID';
                        }
                        break;

                    // [MAX] - Checks if variable is lower or equal than max
                    case 'max':
                        if (is_array($data)) {
                            if (count($data) > $rule[1]) $result['max'] = 'INVALID';
                        } else if (is_string($data)) {
                            if (strlen($data) > $rule[1]) $result['max'] = 'INVALID';
                        } else {
                            if ($data > $rule[1]) $result['max'] = 'INVALID';
                        }
                        break;
                    
                    // [SIZE] - Checks if variable size equals to size
                    case 'size':
                        if (is_array($data)) {
                            if (count($data) != $rule[1]) $result['size'] = 'INVALID';
                        } else if (is_string($data)) {
                            if (strlen($data) != $rule[1]) $result['size'] = 'INVALID';
                        } else {
                            if ($data != $rule[1]) $result['size'] = 'INVALID';
                        }
                        break;
                    
                    // [EMAIL] - Checks if variable is a valid email
                    case 'email':
                        if(!filter_var($data, FILTER_VALIDATE_EMAIL)) $result['email'] = 'INVALID';
                        break;

                    // [URL] - Checks if variable is a valid URL
                    case 'url':
                        if (!filter_var($data, FILTER_VALIDATE_URL)) $result['url'] = 'INVALID';
                        break;

                    // [ALPHA] - Checks if variable is alphabetic
                    case 'alpha':
                        if(!preg_match('/^[a-z]+$/i', $data)) $result['alpha'] = 'INVALID';
                        break;

                    // [NUMERIC] - Checks if variable is a number
                    case 'numeric':
                        if(!is_numeric($data)) $result['numeric'] = 'INVALID';
                        break;
                    
                    // [ALPHANUMERIC] - Checks if variable is alphanumeric
                    case 'alphanumeric':
                        if (!preg_match('/^[a-z0-9]+$/i', $data)) $result['alphanumeric'] = 'INVALID';
                        break;

                    // [ALPHADASH] - Checks if variable is alphanumeric or has dashes/underscores
                    case 'alphadash':
                        if (!preg_match('/^[a-z0-9-_]+$/i', $data)) $result['alphadash'] = 'INVALID';
                        break;

                    // [REGEX] - Checks if variable matches a regex pattern
                    case 'regex':
                        if(!preg_match($rule[1], $data)) $result['regex'] = 'INVALID';
                        break;
                    
                    // [ARRAY] - Checks if variable is an array
                    case 'array':
                        if(!is_array($data)) $result['array'] = 'INVALID';
                        break;

                    // [DATE] - Checks if variable is a valid date
                    case 'date':
                        if (!strtotime($data)) $result['date'] = 'INVALID';
                        break;

                    // [STRING] - Checks if variable is a string
                    case 'string':
                        if (!is_string($data)) $result['string'] = 'INVALID';
                        break;

                    // [INTEGER] - Checks if variable is an integer
                    case 'integer':
                        if (!is_int($data)) $result['integer'] = 'INVALID';
                        break;
                    
                    // [OBJECT] - Checks if variable is an object
                    case 'object':
                        if(!is_object($data)) $result['object'] = 'INVALID';
                        break;

                    // [BOOLEAN] - Checks if variable is a boolean
                    case 'boolean':
                        if(!is_bool($data)) $result['boolean'] = 'INVALID';
                        break;

                    // [VALUE] - Checks if variable matches value
                    case 'value':
                        if($data != $rule[1]) $result['value'] = 'INVALID';
                        break;

                    // [NOT] - Checks if variable does not match value
                    case 'not':
                        if ($data == $rule[1]) $result['not'] = 'INVALID';
                        break;
                    
                    // [EMPTY] - Check if variable is empty
                    case 'empty':
                        if (is_string($data)){
                            if(trim($data) != '') $result['empty'] = 'INVALID';
                        }else{
                            if (isset($data)){
                                if(!empty($data)) $result['empty'] = 'INVALID';
                            }
                        }
                        break;

                    // [ENDSWITH] - Check if variable ends with string
                    case 'endswith':
                        if (Util::endsWith($data, $rule[1])) $result['endswith'] = 'INVALID';
                        break;

                    // [STARTSWITH] - Check if variable starts with string
                    case 'startswith':
                        if (Util::startsWith($data, $rule[1])) $result['startswith'] = 'INVALID';
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