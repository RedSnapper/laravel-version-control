<?php

namespace Redsnapper\LaravelVersionControl\Tests\Fixtures\Models;

use Exception;
use Redsnapper\LaravelVersionControl\Models\BaseModel;
use Redsnapper\LaravelVersionControl\Tests\Fixtures\TestPermissionsRegistrar;

/**
 * App\Models\Role
 *
 * @property string category_unique_link
 * @property string $name
 * @property int $hidden
 * @property int $level
 * @property int $view
 * @property string $comment
 * @property string $alphasort
 * @property string $active
 */
class Role extends BaseModel
{
    protected $versionsTable = 'role_versions';

    protected $fillable = ['uid','vc_version','vc_active','category_uid','name','hidden','level',
        'view','comment','alphasort','active'];

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
        return $this->belongsToMany(User::class, 'role_users',
            'role_uid',
            'user_uid','uid', 'uid');
    }

    public static function activeRoles()
    {
        if(auth()->user()->can('manage-roles')) {
            return with(new static)::where('active',1)->orderBy('name')->pluck('name','uid');
        } else {
            return with(new static)::where('active',1)->where('public',1)->orderBy('name')->pluck('name','uid');
        }
    }

    /**
     * @param $permission
     * @throws Exception
     */
    public function givePermissionTo($permission)
    {
        $permissionKey = $this->getPermissionKey($permission);

        $this->permissions()->attach($permissionKey);

        $this->forgetCachedPermissions();
    }

    /**
     * @param $permission
     * @return $this
     * @throws Exception
     */
    public function removePermission($permission)
    {
        $permissionKey = $this->getPermissionKey($permission);

        $permissionRole = PermissionRole::where('permission_uid', $permissionKey)
            ->where('role_uid', $this->uid)
            ->firstOrFail();

        $permissionRole->delete();

        $this->forgetCachedPermissions();

        return $this;
    }

    /**
     * @param $permission
     * @return bool
     * @throws Exception
     */
    public function hasPermission($permission)
    {
        $permissionKey = $this->getPermissionKey($permission);

        if($this->permissions()->wherePivot('vc_active', 1)
            ->where('permission_uid', $permissionKey)->first()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $permission
     * @return string
     * @throws Exception
     */
    private function getPermissionKey($permission): string
    {
        if (is_string($permission)) {
            $permission = Permission::findByName($permission)->uid;
        }

        if($permission instanceof Permission){
            $permission = $permission->uid;
        }

        return $permission;
    }

    /**
     * Forget the cached permissions.
     */
    private function forgetCachedPermissions()
    {
        app(TestPermissionsRegistrar::class)->forgetCachedPermissions();
    }
}
