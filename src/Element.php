<?php

namespace Glowie\Core;

use Glowie\Core\Traits\ElementTrait;
use JsonSerializable;

/**
 * Generic safe object instance for Glowie application.
 * @category Resource
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 * @see https://glowie.gabrielsilva.dev.br/docs/latest/forms-and-data/element
 */
class Element implements JsonSerializable
{
    use ElementTrait;

    /**
     * Creates a new Element.
     * @param array $data (Optional) An associative array with the initial data to parse.
     * @param bool $recursive (Optional) True if the data must be parsed recursively.
     */
    public function __construct(array $data = [], bool $recursive = false)
    {
        $this->__constructTrait($data, $recursive);
    }

    /**
     * Creates a new Element in a static-binding.
     * @param array $data (Optional) An associative array with the initial data to parse.
     * @param bool $recursive (Optional) True if the data must be parsed recursively.
     * @return $this Returns a new Element.
     */
    public static function make(array $data = [], bool $recursive = false)
    {
        return new static($data, $recursive);
    }
}
