<?php

namespace Redsnapper\LaravelVersionControl\Tests\Fixtures\Models;

use Exception;
use Redsnapper\LaravelVersionControl\Models\BaseModel;
use Redsnapper\LaravelVersionControl\Models\Traits\BelongsToRoles;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Redsnapper\LaravelVersionControl\Tests\Fixtures\TestPermissionsRegistrar;

/**
 * App\Models\Permission
 *
 * @property string $name
 * @property int $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Permission extends BaseModel
{
    protected $versionsTable = 'permission_versions';

    protected $fillable = ['unique_key','vc_version','vc_active','name','active'];

    use BelongsToRoles;

    /**
     * @return BelongsToMany|Role[]
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'permission_roles',
            'permission_unique_key',
            'role_unique_key','unique_key', 'unique_key');
    }

    /**
     * @param  string  $name
     * @return Permission|null
     * @throws Exception
     */
    public static function findByName(string $name): ?self
    {
        $permission = static::getPermissions()->get($name);

        if(!$permission) {
            throw new Exception("Permission {$name} does not exist");
        }

        return $permission;
    }

    /**
     * Get the current cached activities.
     *
     * @return Collection
     */
    protected static function getPermissions(): Collection
    {
        return app(TestPermissionsRegistrar::class)->getPermissions();
    }


}
