<?php

namespace Redsnapper\LaravelVersionControl\Models\Traits;

use Redsnapper\LaravelVersionControl\Exceptions\ReadOnlyException;

trait ReadOnlyModel
{
    /**
     * Throws ReadOnlyException on forceCreate
     * @param array $attributes
     * @throws ReadOnlyException
     */
    public static function forceCreate(array $attributes)
    {
        throw new ReadOnlyException(__FUNCTION__, get_called_class());
    }

    /**
     * Throws ReadOnlyException on firstOrCreate
     * @param  array  $attributes
     * @param  array  $values
     * @throws ReadOnlyException
     */
    public static function firstOrCreate(array $attributes, array $values = [])
    {
        throw new ReadOnlyException(__FUNCTION__, get_called_class());
    }

    /**
     * Throws ReadOnlyException on push
     * @throws ReadOnlyException
     */
    public function push()
    {
        throw new ReadOnlyException(__FUNCTION__, get_called_class());
    }

    /**
     * Throws ReadOnlyException on touch
     * @throws ReadOnlyException
     */
    public function touch()
    {
        throw new ReadOnlyException(__FUNCTION__, get_called_class());
    }

    /**
     * Throws ReadOnlyException on insert
     * @throws ReadOnlyException
     */
    public function insert()
    {
        throw new ReadOnlyException(__FUNCTION__, get_called_class());
    }
}
