<?php

namespace Redsnapper\LaravelVersionControl\Tests\Fixtures\Models;

use Exception;
use Redsnapper\LaravelVersionControl\Models\BaseModel;

/**
 * App\Models\Job
 *
 * @property string $user_uid
 * @property string $title
 */
class Job extends BaseModel
{
    protected $fillable = ['user_uid','title'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
