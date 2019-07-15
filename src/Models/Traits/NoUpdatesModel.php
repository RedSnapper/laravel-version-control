<?php

namespace Redsnapper\LaravelVersionControl\Models\Traits;

use Redsnapper\LaravelVersionControl\Exceptions\ReadOnlyException;

trait NoUpdatesModel
{
    /**
     * @param array $attributes
     * @param array $values
     */
    public static function updateOrCreate(array $attributes, array $values = [])
    {
        throw new ReadOnlyException(__FUNCTION__, get_called_class());
    }

    public function update(array $attributes = [], array $options = [])
    {
        throw new ReadOnlyException(__FUNCTION__, get_called_class());
    }
}
