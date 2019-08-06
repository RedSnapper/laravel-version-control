<?php

namespace Redsnapper\LaravelVersionControl\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Redsnapper\LaravelVersionControl\Models\Traits\NoDeletesModel;
use Redsnapper\LaravelVersionControl\Models\Traits\NoUpdatesModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $uid
 * @property int $vc_version
 * @property int $vc_parent
 * @property int $vc_active
 * @property string $vc_modifier_uid
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Versioned extends Model
{
    protected $primaryKey = 'uid';
    public $incrementing = false;
    protected $guarded = [];

    use NoDeletesModel,
      NoUpdatesModel;

    public static function boot()
    {
        parent::boot();

        /**
         * On creating, we verify whether our new entry has a previous version or not, increment the version and see if
         * we need to increment the branch too.
         */
        static::creating(function (Versioned $model) {

            $uid = Str::uuid();
            $model->uid = $uid;
            if (auth()->check()) {
                $model->vc_modifier_uid = auth()->user()->uid;
            }
        });
    }

    /**
     * Create version from a new model
     *
     * @param  array  $attributes
     * @return Versioned
     */
    public function createFromNew(array $attributes):self
    {
        $this->fill($attributes);
        $this->model_uid = Str::uuid();
        $this->vc_active = true;
        $this->vc_parent = null;

        return $this;
    }

    /**
     * Create version from existing model
     *
     * @param  array  $attributes
     * @return Versioned
     */
    public function createFromExisting(array $attributes):self
    {
        $this->fill(Arr::except($attributes,['vc_version_uid']));

        $this->model_uid = $attributes['uid'];

        // The previous version
        $this->vc_parent = $attributes['vc_version_uid'];

        return $this;
    }

    public function parent():HasOne
    {
        $instance = $this->newRelatedInstance(Versioned::class);
        $instance->setTable($this->getTable());

        $foreignKey = $this->getKeyName();

        $localKey = 'vc_parent';

        return $this->newHasOne($instance->newQuery(), $this, $instance->getTable().'.'.$foreignKey, $localKey);
    }


    /**
     * Restore this version
     *
     * @return bool
     */
    public function restore(BaseModel $model)
    {

        $model->fill(array_merge(
          Arr::except($this->attributes,['uid','model_uid','vc_parent','created_at','updated_at']),
          [
            'vc_version_uid'=> $this->attributes['uid']
          ]
        ));

        return tap($model)->save();
    }

    public function getIsNewModelAttribute(): bool
    {
        return is_null($this->vc_version);
    }

    public function modifyingUser(): BelongsTo
    {
        return $this->belongsTo(config('rs-version-control.user'), 'vc_modifier_uid', 'uid');
    }

    /**
     * Cast vc active to boolean.
     *
     * @param  int  $value
     * @return bool
     */
    public function getVcActiveAttribute($value)
    {
        return (bool) $value;
    }

    /**
     * Is this version active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->vc_active;
    }

    /**
     * Was this version a delete action
     *
     * @return bool
     */
    public function isDeleted(): bool
    {
        return !$this->vc_active;
    }

    public function toModelArray()
    {
        return Arr::except($this->attributes,['uid','model_uid','vc_parent']);
    }


}
