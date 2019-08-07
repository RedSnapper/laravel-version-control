<?php

namespace Redsnapper\LaravelVersionControl\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class SoftDeletingScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $builder->where("vc_active");
    }

    /**
     * Extend the query builder with the needed functions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    public function extend(Builder $builder)
    {
        $builder->macro('withTrashed', function (Builder $builder, $withTrashed = true) {
            //if (! $withTrashed) {
            //    return $builder->withoutTrashed();
            //}
            return $builder->withoutGlobalScope($this);
        });
    }
}
