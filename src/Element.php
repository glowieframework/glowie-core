<?php
    namespace Glowie\Core;

    use Glowie\Core\Traits\ElementTrait;

    /**
     * Generic safe object instance for Glowie application.
     * @category Object
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.0
     */
    class Element{
        use ElementTrait;

        /**
         * Creates a new Element.
         * @param array $data (Optional) An associative array with the initial data to parse.
         */
        public function __construct(array $data = []){
            $this->__constructTrait($data);
        }

    }

?>