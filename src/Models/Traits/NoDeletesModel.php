<?php

namespace Redsnapper\LaravelVersionControl\Models\Traits;

use Redsnapper\LaravelVersionControl\Exceptions\ReadOnlyException;

trait NoDeletesModel
{
    /**
     * Throws ReadOnlyException on delete
     * @throws ReadOnlyException
     */
    public function delete()
    {
        throw new ReadOnlyException(__FUNCTION__, get_called_class());
    }

    /**
     * Throws ReadOnlyException on destroy
     * @param mixed $ids
     * @throws ReadOnlyException
     */
    public static function destroy($ids)
    {
        throw new ReadOnlyException(__FUNCTION__, get_called_class());
    }

    /**
     * Throws ReadOnlyException on forceDelete
     * @throws ReadOnlyException
     */
    public function forceDelete()
    {
        throw new ReadOnlyException(__FUNCTION__, get_called_class());
    }

    /**
     * Throws ReadOnlyException on performDeleteOnModel
     * @throws ReadOnlyException
     */
    public function performDeleteOnModel()
    {
        throw new ReadOnlyException(__FUNCTION__, get_called_class());
    }

    /**
     * Throws ReadOnlyException on truncate
     * @throws ReadOnlyException
     */
    public function truncate()
    {
        throw new ReadOnlyException(__FUNCTION__, get_called_class());
    }
}
