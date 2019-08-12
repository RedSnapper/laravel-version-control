<?php

namespace Redsnapper\LaravelVersionControl\Tests\Fixtures\Models;


use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use Redsnapper\LaravelVersionControl\Models\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['role_uid','email','password'];
    protected $hidden = ['remember_token'];

    public function isCurrentUser(): bool
    {
        return $this->uid === auth()->user()->uid;
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
}
