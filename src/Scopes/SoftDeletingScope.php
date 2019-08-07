<?php

namespace Redsnapper\LaravelVersionControl\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class SoftDeletingScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $builder->where($model->getTable() . ".vc_active", 1);
    }

    /**
     * Extend the query builder with the needed functions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    public function extend(Builder $builder)
    {
        $builder->macro('withTrashed', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });

        $builder->macro('onlyTrashed', function (Builder $builder) {

            $builder->withoutGlobalScope($this)->where('vc_active',0);

            return $builder;
        });
    }
}
