<?php

namespace Redsnapper\LaravelVersionControl\Tests\Fixtures\Models;

use Redsnapper\LaravelVersionControl\Models\BaseModel;

/**
 * App\Models\PermissionRole
 *
 * @property string $permission_unique_key
 * @property string $role_unique_key
 */
class PermissionRole extends BaseModel
{
    protected $versionsTable = 'permission_role_versions';

    protected $fillable = ['unique_key','vc_version','vc_active','permission_unique_key','role_unique_key'];

    public $key1 = "permission_unique_key";
    public $key2 = "role_unique_key";
}
