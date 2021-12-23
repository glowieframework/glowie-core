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
     * @version 1.1
     */
    class UnitTest{

        /**
         * Asserts that a variable matches an expected value and its type.
         * @param mixed $variable Variable to be tested.
         * @param mixed $value Expected value.
         * @throws TestException Throws an unit test exception if the test fails.
         */
        final protected function assertEquals($variable, $value){
            if($variable !== $value) throw new TestException('Tested variable does not equals the expected value');
        }

        /**
         * Asserts that a variable does not match a value and its type.
         * @param mixed $variable Variable to be tested.
         * @param mixed $value Value to check.
         * @throws TestException Throws an unit test exception if the test fails.
         */
        final protected function assertNotEquals($variable, $value){
            if($variable === $value) throw new TestException('Tested variable equals the value');
        }

        /**
         * Asserts that a variable is null.
         * @param mixed $variable Variable to be tested.
         * @throws TestException Throws an unit test exception if the test fails.
         */
        final protected function assertIsNull($variable){
            if(!is_null($variable)) throw new TestException('Tested variable is not null');
        }

        /**
         * Asserts that a variable is not null.
         * @param mixed $variable Variable to be tested.
         * @throws TestException Throws an unit test exception if the test fails.
         */
        final protected function assertIsNotNull($variable){
            if(is_null($variable)) throw new TestException('Tested variable is null');
        }

        /**
         * Asserts that a specific key exists in an array.
         * @param array $array Array to be tested.
         * @param mixed $key Key to be checked.
         * @throws TestException Throws an unit test exception if the test fails.
         */
        final protected function assertKeyExists(array $array, $key){
            if(!is_array($array)) throw new TestException('Tested variable is not an array');
            if(!array_key_exists($key, $array)) throw new TestException('Key "' . $key . '" does not exist in tested array');
        }

        /**
         * Asserts that a specific key does not exist in an array.
         * @param array $array Array to be tested.
         * @param mixed $key Key to be checked.
         * @throws TestException Throws an unit test exception if the test fails.
         */
        final protected function assertKeyDoesntExist(array $array, $key){
            if(!is_array($array)) throw new TestException('Tested variable is not an array');
            if(array_key_exists($key, $array)) throw new TestException('Key "' . $key . '" exists in tested array');
        }

        /**
         * Asserts that a specific property exists in an object or class.
         * @param object|string $object Object or class name to be tested.
         * @param string $property Property name to be checked.
         * @throws TestException Throws an unit test exception if the test fails.
         */
        final protected function assertPropertyExists($object, string $property){
            if(!property_exists($object, $property)) throw new TestException('Property "' . $property . '" does not exist in tested object');
        }

        /**
         * Asserts that a specific property does not exist in an object or class.
         * @param object|string $object Object or class name to be tested.
         * @param string $property Property name to be checked.
         * @throws TestException Throws an unit test exception if the test fails.
         */
        final protected function assertPropertyDoesntExist($object, string $property){
            if(property_exists($object, $property)) throw new TestException('Property "' . $property . '" exists in tested object');
        }

        /**
         * Asserts that an array contains a specific value.
         * @param array $array Array to be tested.
         * @param mixed $value Value to search in the array.
         * @param bool $strict (Optional) Also check for the value type when searching.
         * @throws TestException Throws an unit test exception if the test fails.
         */
        final protected function assertArrayContains(array $array, $value, bool $strict = false){
            if(!is_array($array)) throw new TestException('Tested variable is not an array');
            if(!in_array($value, $array, $strict)) throw new TestException('Value does not exist in tested array');
        }

        /**
         * Asserts that an array does not contain a specific value.
         * @param array $array Array to be tested.
         * @param mixed $value Value to search in the array.
         * @param bool $strict (Optional) Also check for the value type when searching.
         * @throws TestException Throws an unit test exception if the test fails.
         */
        final protected function assertArrayDoesntContain(array $array, $value, bool $strict = false){
            if(!is_array($array)) throw new TestException('Tested variable is not an array');
            if(in_array($value, $array, $strict)) throw new TestException('Value exists in tested array');
        }

        /**
         * Asserts that a variable or equals true.
         * @param mixed $variable Variable to be tested.
         * @throws TestException Throws an unit test exception if the test fails.
         */
        final protected function assertIsTrue($variable){
            if($variable !== true) throw new TestException('Tested variable is not true');
        }

        /**
         * Asserts that a variable equals false.
         * @param mixed $variable Variable to be tested.
         * @throws TestException Throws an unit test exception if the test fails.
         */
        final protected function assertIsFalse($variable){
            if($variable !== false) throw new TestException('Tested variable is not false');
        }

        /**
         * Asserts that a variable is not empty.
         * @param mixed $variable Variable to be tested.
         * @throws TestException Throws an unit test exception if the test fails.
         */
        final protected function assertIsEmpty($variable){
            if(!Util::isEmpty($variable)) throw new TestException('Tested variable is not empty');
        }

        /**
         * Asserts that a variable is not empty.
         * @param mixed $variable Variable to be tested.
         * @throws TestException Throws an unit test exception if the test fails.
         */
        final protected function assertIsNotEmpty($variable){
            if(Util::isEmpty($variable)) throw new TestException('Tested variable is empty');
        }

    }

?>