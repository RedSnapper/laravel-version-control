<?php

namespace Redsnapper\LaravelVersionControl\Models\Traits;

use Redsnapper\LaravelVersionControl\Scopes\ActiveScope;

trait ActiveOnlyModel
{
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new ActiveScope());
    }
}
