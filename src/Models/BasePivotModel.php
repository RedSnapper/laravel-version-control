<?php

namespace Redsnapper\LaravelVersionControl\Models;

use Illuminate\Database\Eloquent\Relations\Concerns\AsPivot;

class BasePivotModel extends BaseModel
{
    use AsPivot;

    protected $guarded = [];

}
