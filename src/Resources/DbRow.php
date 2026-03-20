<?php

namespace Glowie\Core\Resources;

use Glowie\Core\Element;

/**
 * Resource to abstract a database table row.
 * @category Resource
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 */
class DbRow extends Element
{

    /**
     * Gets the name of the columns available on the row.
     * @return array Return an array with the columns.
     */
    public function getColumns()
    {
        return $this->keys();
    }
}
