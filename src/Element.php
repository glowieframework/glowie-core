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
 * @link https://gabrielsilva.dev.br/glowie
 * @see https://gabrielsilva.dev.br/glowie/docs/latest/forms-and-data/element
 */
class Element implements JsonSerializable
{
    use ElementTrait;

    /**
     * Creates a new Element.
     * @param array $data (Optional) An associative array with the initial data to parse.
     */
    public function __construct(array $data = [])
    {
        $this->__constructTrait($data);
    }
}
