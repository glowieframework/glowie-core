<?php
    namespace Glowie\Core;

    use Glowie\Core\Traits\ElementTrait;
    use JsonSerializable;

    /**
     * Generic safe object instance for Glowie application.
     * @category Object
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) Glowie
     * @license MIT
     * @link https://eugabrielsilva.tk/glowie
     */
    class Element implements JsonSerializable{
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