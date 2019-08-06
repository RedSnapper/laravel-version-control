<?php

namespace Redsnapper\LaravelVersionControl\Tests\Fixtures\Models;

use Exception;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Redsnapper\LaravelVersionControl\Models\BaseModel;
use Redsnapper\LaravelVersionControl\Models\Traits\BelongsToRoles;
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
 * @property string $role_uid
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

    protected $fillable = ['uid','vc_version_uid','vc_active','role_uid','email','password'];
    protected $hidden = ['remember_token'];

    public function isCurrentUser(): bool
    {
        return $this->uid === auth()->user()->uid;
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function job()
    {
        return $this->hasOne(Job::class);
    }

    /**
     * @return BelongsTo
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * @param  string  $permission
     * @return bool
     * @throws Exception
     */
    public function hasPermissionTo(string $permission): bool
    {
        $permission = Permission::findByName($permission);

        if ($permission) {
            return $permission->role()->pluck('role_uid')
                ->intersect($this->role_uid)->isNotEmpty();
        }

        return false;
    }
}
