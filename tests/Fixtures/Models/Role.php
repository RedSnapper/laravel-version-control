<?php

namespace Redsnapper\LaravelVersionControl\Tests\Fixtures\Models;

use Redsnapper\LaravelVersionControl\Models\BaseModel;

class Role extends BaseModel
{

    protected $fillable = ['category_uid','name'];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'permission_roles',
            'role_uid',
            'permission_uid','uid', 'uid')
            ->withPivot(['uid','vc_version','vc_active'])
            ->using(PermissionRole::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
