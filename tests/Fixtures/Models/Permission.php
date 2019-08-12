<?php

namespace Redsnapper\LaravelVersionControl\Tests\Fixtures\Models;


use Redsnapper\LaravelVersionControl\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class Permission extends BaseModel
{

    protected $fillable = ['name'];

    /**
     * @return BelongsToMany|Role[]
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class)
          ->using(PermissionRole::class)
          ->withPivot('flag');
    }

}
