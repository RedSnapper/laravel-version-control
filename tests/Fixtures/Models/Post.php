<?php

namespace Redsnapper\LaravelVersionControl\Tests\Fixtures\Models;

use Exception;
use Redsnapper\LaravelVersionControl\Models\BaseModel;
use Redsnapper\LaravelVersionControl\Tests\Fixtures\TestPermissionsRegistrar;

/**
 * App\Models\Post
 *
 * @property string $user_uid
 * @property string $title
 * @property string $content
 */
class Post extends BaseModel
{

    protected $fillable = ['uid','vc_version','vc_active','user_uid','title','content'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
