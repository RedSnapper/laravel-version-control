<?php

namespace Redsnapper\LaravelVersionControl\Tests\Fixtures\Models;

use Redsnapper\LaravelVersionControl\Models\BaseModel;
use Illuminate\Support\Carbon;

/**
 * App\Models\Auth\RoleUser
 *
 * @property int $id
 * @property int $user_id
 * @property int $role_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class RoleUser extends BaseModel
{
    protected $versionsTable = 'role_user_versions';

    protected $fillable = ['uid','vc_version','vc_active','role_uid','user_uid'];

    public $key1 = "role_uid";
    public $key2 = "user_uid";
}
