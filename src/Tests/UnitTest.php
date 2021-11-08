<?php
    namespace Glowie\Core\Tests;

    use Glowie\Core\Exception\TestException;
    use Util;

    /**
     * Unit testing core for Glowie application.
     * @category Tests
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.0
     */
    class UnitTest{

        /**
         * Checks if a variable matches an expected value and its type.
         * @param mixed $variable Variable to be tested.
         * @param mixed $value Expected value.
         * @throws TestException Throws an unit test exception if the test fails.
         */
        final protected function equals($variable, $value){
            if($variable !== $value) throw new TestException('Tested variable does not equals the expected value');
        }

        /**
         * Checks if a variable does not match a value and its type.
         * @param mixed $variable Variable to be tested.
         * @param mixed $value Value to check.
         * @throws TestException Throws an unit test exception if the test fails.
         */
        final protected function notEquals($variable, $value){
            if($variable === $value) throw new TestException('Tested variable equals the value');
        }

        /**
         * Checks if a variable is null.
         * @param mixed $variable Variable to be tested.
         * @throws TestException Throws an unit test exception if the test fails.
         */
        final protected function isNull($variable){
            if(!is_null($variable)) throw new TestException('Tested variable is not null');
        }

        /**
         * Checks if a variable is not null.
         * @param mixed $variable Variable to be tested.
         * @throws TestException Throws an unit test exception if the test fails.
         */
        final protected function isNotNull($variable){
            if(is_null($variable)) throw new TestException('Tested variable is null');
        }

        /**
         * Checks if a specific key exists in an array.
         * @param array $array Array to be tested.
         * @param mixed $key Key to be checked.
         * @throws TestException Throws an unit test exception if the test fails.
         */
        final protected function keyExists(array $array, $key){
            if(!is_array($array)) throw new TestException('Tested variable is not an array');
            if(!array_key_exists($key, $array)) throw new TestException('Key "' . $key . '" does not exist in tested array');
        }

        /**
         * Checks if a specific key does not exist in an array.
         * @param array $array Array to be tested.
         * @param mixed $key Key to be checked.
         * @throws TestException Throws an unit test exception if the test fails.
         */
        final protected function keyDoesntExist(array $array, $key){
            if(!is_array($array)) throw new TestException('Tested variable is not an array');
            if(array_key_exists($key, $array)) throw new TestException('Key "' . $key . '" exists in tested array');
        }

        /**
         * Checks if a specific property exists in an object or class.
         * @param object|string $object Object or class name to be tested.
         * @param string $property Property name to be checked.
         * @throws TestException Throws an unit test exception if the test fails.
         */
        final protected function propertyExists($object, string $property){
            if(!property_exists($object, $property)) throw new TestException('Property "' . $property . '" does not exist in tested object');
        }

        /**
         * Checks if a specific property does not exist in an object or class.
         * @param object|string $object Object or class name to be tested.
         * @param string $property Property name to be checked.
         * @throws TestException Throws an unit test exception if the test fails.
         */
        final protected function propertyDoesntExist($object, string $property){
            if(property_exists($object, $property)) throw new TestException('Property "' . $property . '" exists in tested object');
        }

        /**
         * Checks if an array contains a specific value.
         * @param array $array Array to be tested.
         * @param mixed $value Value to search in the array.
         * @param bool $strict (Optional) Also check for the value type when searching.
         * @throws TestException Throws an unit test exception if the test fails.
         */
        final protected function arrayContains(array $array, $value, bool $strict = false){
            if(!is_array($array)) throw new TestException('Tested variable is not an array');
            if(!in_array($value, $array, $strict)) throw new TestException('Value does not exist in tested array');
        }

        /**
         * Checks if an array does not contain a specific value.
         * @param array $array Array to be tested.
         * @param mixed $value Value to search in the array.
         * @param bool $strict (Optional) Also check for the value type when searching.
         * @throws TestException Throws an unit test exception if the test fails.
         */
        final protected function arrayDoesntContain(array $array, $value, bool $strict = false){
            if(!is_array($array)) throw new TestException('Tested variable is not an array');
            if(in_array($value, $array, $strict)) throw new TestException('Value exists in tested array');
        }

        /**
         * Checks if a variable or equals true.
         * @param mixed $variable Variable to be tested.
         * @throws TestException Throws an unit test exception if the test fails.
         */
        final protected function isTrue($variable){
            if($variable !== true) throw new TestException('Tested variable is not true');
        }

        /**
         * Checks if a variable equals false.
         * @param mixed $variable Variable to be tested.
         * @throws TestException Throws an unit test exception if the test fails.
         */
        final protected function isFalse($variable){
            if($variable !== false) throw new TestException('Tested variable is not false');
        }

        /**
         * Checks if a variable is not empty.
         * @param mixed $variable Variable to be tested.
         * @throws TestException Throws an unit test exception if the test fails.
         */
        final protected function isEmpty($variable){
            if(!Util::isEmpty($variable)) throw new TestException('Tested variable is not empty');
        }

        /**
         * Checks if a variable is not empty.
         * @param mixed $variable Variable to be tested.
         * @throws TestException Throws an unit test exception if the test fails.
         */
        final protected function isNotEmpty($variable){
            if(Util::isEmpty($variable)) throw new TestException('Tested variable is empty');
        }

    }

?>