<?php

namespace Redsnapper\LaravelVersionControl\Models;

use Redsnapper\LaravelVersionControl\Models\Traits\HasCompositePrimaryKey;
use Redsnapper\LaravelVersionControl\Models\Traits\NoDeletesModel;
use Redsnapper\LaravelVersionControl\Models\Traits\NoUpdatesModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $uid
 * @property int $vc_version
 * @property int $vc_parent
 * @property int $vc_branch
 * @property int $vc_active
 * @property string $vc_modifier_uid
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Versioned extends Model
{
    protected $primaryKey = ['uid', 'vc_version'];
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
            if (!$model->is_new_model) {

                // We need to grab previous in order to set the correct version
                // Without the possibility of a restore function, we could do this without a query and just incrementing based on the vc_version passed from BaseModel version save
                $previous = (new Versioned())->setTable($model->getTable())
                    ->where('uid', $model->uid)
                    ->orderBy('vc_version','desc')
                    ->orderBy('vc_branch','desc')
                    ->first();

                $model->vc_version = $previous->vc_version + 1;

                // Lets check if we are doing a restore, if we are - increment the branch. If not, just use same branch we were on before
                if($model->vc_parent !== $previous->vc_version) {
                    $model->vc_branch = $previous->vc_branch + 1;
                } else {
                    $model->vc_branch = $previous->vc_branch;
                }
            } else {
                $model->vc_active = true;
                $model->vc_version = 1;
                $model->vc_parent = null;
                $model->vc_branch = 1;
            }

            // This would almost always fire, but I guess is possible for system initiated changes etc (during nightly builds or something)
            if (auth()->check()) {
                $model->vc_modifier_uid = auth()->user()->uid;
            }
        });
    }

    public function getIsNewModelAttribute():bool
    {
        return is_null($this->vc_version);
    }

    public function modifyingUser(): BelongsTo
    {
        return $this->belongsTo(config('rs-version-control.user'), 'vc_modifier_uid', 'uid');
    }

    public function setBaseModel($name)
    {
        $this->baseModel = $name;
    }
}
