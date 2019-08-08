<?php

namespace Redsnapper\LaravelVersionControl\Tests\Fixtures\Models;

use Redsnapper\LaravelVersionControl\Models\BaseModel;

class Role extends BaseModel
{

    protected $fillable = ['category_uid','name'];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class)->withPivot('region');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
