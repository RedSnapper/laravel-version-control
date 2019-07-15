<?php

namespace Redsnapper\LaravelVersionControl\Tests\Fixtures\Models;

use Redsnapper\LaravelVersionControl\Exceptions\Auth\PermissionDoesNotExist;
use Redsnapper\LaravelVersionControl\Models\BaseModel;
use Redsnapper\LaravelVersionControl\Models\Traits\BelongsToRoles;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

/**
 * App\Models\User
 *
 * @property string $username
 * @property string $email
 * @property string $emailp
 * @property string $password
 * @property string $active
 * @property string|null $remember_token
 * @property-read DatabaseNotificationCollection|DatabaseNotification[] $notifications
 */
class User extends BaseModel implements
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract
{
    use Notifiable,
        Authenticatable,
        Authorizable,
        CanResetPassword,
        MustVerifyEmail,
        BelongsToRoles;

    protected $versionsTable = 'user_versions';

    protected $fillable = ['unique_key','vc_version','vc_active','username','email','emailp','password','active'];
    protected $hidden = ['remember_token'];

    public function isCurrentUser(): bool
    {
        return $this->unique_key === auth()->user()->unique_key;
    }

    /**
     * @return BelongsToMany|Role[]
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class,
            'role_users',
            'user_unique_key',
            'role_unique_key',
            'unique_key',
            'unique_key'
        );
    }

    /**
     * @param  string  $permission
     * @return bool
     * @throws \Exception
     */
    public function hasPermissionTo(string $permission): bool
    {
        $permission = Permission::findByName($permission);

        if ($permission) {
            return $permission->roles()->pluck('role_unique_key')
                ->intersect($this->roles()->pluck('role_unique_key'))->isNotEmpty();
        }

        return false;
    }

    /**
     * @param  Role  $role
     * @return User
     */
    public function assignRole(Role $role): self
    {
        //TODO: Consider a possible future need for having new versions of users when attaching/detaching roles
        $this->attach($role->unique_key, $this->unique_key, (new RoleUser()));

        return $this;
    }

    /**
     * @param  Role  $role
     * @return User
     */
    public function unAssignRole(Role $role): self
    {
        $roleUser = RoleUser::where('role_unique_key', $role->unique_key)
            ->where('user_unique_key', $this->unique_key)
            ->firstOrFail();

        $roleUser->delete();

        return $this;
    }
}
