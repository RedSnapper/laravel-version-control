<?php

namespace Redsnapper\LaravelVersionControl\Tests\Fixtures\Models;

use Redsnapper\LaravelVersionControl\Models\BaseModel;

/**
 * App\Models\Post
 *
 * @property string $user_uid
 * @property string $title
 * @property string $content
 */
class Post extends BaseModel
{

    protected $fillable = ['user_uid','title','content'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
