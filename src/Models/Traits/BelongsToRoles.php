<?php


namespace Redsnapper\LaravelVersionControl\Models\Traits;


use Redsnapper\LaravelVersionControl\Tests\Fixtures\Models\Role;

trait BelongsToRoles
{
    /**
     * @param int|Role $role
     * @return string
     */
    private function getRoleKey($role): string
    {
        if($role instanceof Role){
            $role = $role->unique_key;
        }

        return $role;
    }

    /**
     * @param string|int|Role $role
     * @return bool
     */
    public function belongsToRole($role): bool
    {
        $roleKey = $this->getRoleKey($role);

        if($this->roles()->wherePivot('vc_active',1)
            ->where('role_unique_key', $roleKey)
            ->first()) {
            return true;
        } else {
            return false;
        }
    }
}
