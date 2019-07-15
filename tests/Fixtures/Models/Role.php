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

    protected $fillable = ['unique_key','vc_version','vc_active','category_unique_key','name','hidden','level',
        'view','comment','alphasort','active'];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'permission_roles',
            'role_unique_key',
            'permission_unique_key','unique_key', 'unique_key');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'role_users',
            'role_unique_key',
            'user_unique_key','unique_key', 'unique_key');
    }

    public static function activeRoles()
    {
        if(auth()->user()->can('manage-roles')) {
            return with(new static)::where('active',1)->orderBy('name')->pluck('name','unique_key');
        } else {
            return with(new static)::where('active',1)->where('public',1)->orderBy('name')->pluck('name','unique_key');
        }
    }

    /**
     * @param $permission
     * @throws Exception
     */
    public function givePermissionTo($permission)
    {
        $permissionKey = $this->getPermissionKey($permission);

        $this->attach($permissionKey, $this->unique_key, (new PermissionRole()));

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

        $permissionRole = PermissionRole::where('permission_unique_key', $permissionKey)
            ->where('role_unique_key', $this->unique_key)
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
            ->where('permission_unique_key', $permissionKey)->first()) {
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
            $permission = Permission::findByName($permission)->unique_key;
        }

        if($permission instanceof Permission){
            $permission = $permission->unique_key;
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
