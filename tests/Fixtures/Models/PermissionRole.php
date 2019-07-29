<?php

namespace Redsnapper\LaravelVersionControl\Tests\Fixtures\Models;

use Redsnapper\LaravelVersionControl\Models\BasePivotModel;

/**
 * App\Models\PermissionRole
 *
 * @property string $permission_uid
 * @property string $role_uid
 */
class PermissionRole extends BasePivotModel
{
    protected $table = 'permission_roles';
    protected $versionsTable = 'permission_role_versions';

    protected $fillable = ['uid','vc_version','vc_active','permission_uid','role_uid'];

    public $key1 = "permission_uid";
    public $key2 = "role_uid";
}
