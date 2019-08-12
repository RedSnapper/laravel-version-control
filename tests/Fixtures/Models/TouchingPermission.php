<?php
/**
 * Created by PhpStorm.
 * User: paramdeepdhaliwal
 * Date: 2019-08-12
 * Time: 16:13
 */

namespace Redsnapper\LaravelVersionControl\Tests\Fixtures\Models;

use Redsnapper\LaravelVersionControl\Models\BaseModel;

class TouchingPermission extends BaseModel
{

    public $table = 'permissions';
    protected $fillable = ['name'];
    protected $touches = ['roles'];

    public function roles()
    {
        return $this->belongsToMany(Role::class,'permission_role', 'permission_uid', 'role_uid')
            ->withPivot('flag');
    }

}