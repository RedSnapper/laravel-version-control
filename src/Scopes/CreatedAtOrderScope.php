<?php

namespace Redsnapper\LaravelVersionControl\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class CreatedAtOrderScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $builder->orderBy('created_at', 'desc');
    }

    /**
     * @param  Builder  $builder
     */
    public function extend(Builder $builder)
    {
        $builder->macro('withoutCreatedAtOrder', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }
}
