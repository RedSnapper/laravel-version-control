<?php

namespace Redsnapper\LaravelVersionControl\Tests\Fixtures\Models;

use Redsnapper\LaravelVersionControl\Models\BaseModel;

class Role extends BaseModel
{

    protected $fillable = ['category_uid', 'name'];

    protected $touches = ['touchingPermissions'];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class)->withPivot('flag');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function touchingPermissions()
    {
        return $this->belongsToMany(TouchingPermission::class,
            'permission_role',
            'permission_uid',
            'role_uid')->withPivot('flag');
    }
}
