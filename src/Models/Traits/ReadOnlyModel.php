<?php

namespace Redsnapper\LaravelVersionControl\Models\Traits;

use Redsnapper\LaravelVersionControl\Exceptions\ReadOnlyException;

trait ReadOnlyModel
{


    /**
     * Throws ReadOnlyException on insert
     * @throws ReadOnlyException
     */
    public function insert()
    {
        throw new ReadOnlyException(__FUNCTION__, get_called_class());
    }
}
