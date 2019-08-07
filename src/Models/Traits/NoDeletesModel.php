<?php

namespace Redsnapper\LaravelVersionControl\Models\Traits;

use Redsnapper\LaravelVersionControl\Exceptions\ReadOnlyException;

trait NoDeletesModel
{
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
     * Throws ReadOnlyException on truncate
     * @throws ReadOnlyException
     */
    public function truncate()
    {
        throw new ReadOnlyException(__FUNCTION__, get_called_class());
    }
}
