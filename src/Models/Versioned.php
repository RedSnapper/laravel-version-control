<?php

namespace Redsnapper\LaravelVersionControl\Models;

use Redsnapper\LaravelVersionControl\Models\Traits\HasCompositePrimaryKey;
use Redsnapper\LaravelVersionControl\Models\Traits\NoDeletesModel;
use Redsnapper\LaravelVersionControl\Models\Traits\NoUpdatesModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $unique_key
 * @property int $vc_version
 * @property int $vc_parent
 * @property int $vc_branch
 * @property int $vc_active
 * @property string $vc_modifier_unique_key
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Versioned extends Model
{
    protected $primaryKey = ['unique_key', 'vc_version'];
    public $incrementing = false;
    protected $baseModel;
    protected $guarded = [];

    use NoDeletesModel,
      NoUpdatesModel,
      HasCompositePrimaryKey;

    public static function boot()
    {
        parent::boot();

        /**
         * On creating, we verify whether our new entry has a previous version or not, increment the version and see if
         * we need to increment the branch too.
         */
        static::creating(function (Versioned $model) {
            //$previous = (new Versioned())->setTable($model->getTable())
            //    ->where('unique_key', $model->unique_key)
            //    ->orderBy('vc_version','desc')
            //    ->orderBy('vc_branch','desc')
            //    ->first();


            if (!$model->is_new_model) {
                $model->vc_version = $model->vc_version + 1;
                $model->vc_parent = $model->vc_parent ?? $model->vc_version; // Only overwrite if not already set (during restore procedure this will be set)

                // Set branch
                $branch = (new Versioned())->setTable($model->getTable())
                  ->where('unique_key', $model->unique_key)
                  ->where('vc_parent', $model->vc_parent)
                  ->orderBy('vc_branch', 'desc')->first();
                $model->vc_branch = !empty($branch) ? $branch->vc_branch + 1 : 1;


            } else {
                $model->vc_active = true;
                $model->vc_version = 1;
                $model->vc_parent = null;
                $model->vc_branch = 1;
            }

            if (auth()->check()) {
                $model->vc_modifier_unique_key = auth()->user()->unique_key;
            } else {
                $model->vc_modifier_unique_key = 'kads';
            }
        });
    }

    public function getIsNewModelAttribute():bool
    {
        return is_null($this->vc_version);
    }

    public function modifyingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vc_modifier_unique_key', 'unique_key');
    }

    public function setBaseModel($name)
    {
        $this->baseModel = $name;
    }
}
