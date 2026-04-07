<?php

namespace Glowie\Core\Resources;

use Glowie\Core\Collection;

/**
 * Resource to abstract validation error messages.
 * @category Resource
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 */
class ValidationMessages extends Collection
{

    /**
     * Creates the resource.
     * @param array $data Array with the validation error messages.
     */
    public function __construct(array $data = [])
    {
        return parent::__construct($data, true);
    }

    /**
     * Gets all the validation error messages as a single-level Collection.
     * @return Collection Returns a Collection with all validation error messages.
     */
    public function all()
    {
        $result = [];

        foreach (array_values($this->__data) as $messages) {
            foreach ($messages as $item) {
                $result[] = $item;
            }
        }

        return new Collection($result);
    }

    /**
     * Gets the validation error messages for a specific field.
     * @param mixed $key Field name to get (accepts dot notation keys).
     * @param mixed $default (Optional) Default value to return if the field does not exist.
     * @return Collection Returns a Collection with the validation error messages for the specified field.
     */
    public function get($key, $default = null)
    {
        return parent::get($key, new Collection());
    }
}
