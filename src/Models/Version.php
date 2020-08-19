<?php

namespace Redsnapper\LaravelVersionControl\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Redsnapper\LaravelVersionControl\Exceptions\ReadOnlyException;
use Redsnapper\LaravelVersionControl\Models\Traits\NoDeletesModel;
use Redsnapper\LaravelVersionControl\Models\Traits\NoUpdatesModel;
use Redsnapper\LaravelVersionControl\Scopes\CreatedAtOrderScope;

class Version extends Model
{
    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'uid';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $dates = ['created_at'];

    use NoDeletesModel,
        NoUpdatesModel;

    public static function boot()
    {
        parent::boot();

        static::addGlobalScope(new CreatedAtOrderScope());

        /**
         * On creating, we verify whether our new entry has a previous version or not, increment the version and see if
         * we need to increment the branch too.
         */
        static::creating(function (Version $model) {

            $uid = (string) Str::uuid();
            $model->created_at = $model->freshTimestamp();
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
     * @return Version
     */
    public function createFromNew(array $attributes): self
    {

        $this->forceFill($this->removeBaseModelAttributes($attributes));
        $this->model_uid = (string) Str::uuid();
        $this->vc_active = true;
        $this->vc_parent = null;

        return $this;
    }

    /**
     * Create version from existing model
     *
     * @param  array  $attributes
     * @return Version
     */
    public function createFromExisting(array $attributes): self
    {

        $this->forceFill($this->removeBaseModelAttributes($attributes));

        $this->model_uid = $attributes['uid'];

        // The previous version
        $this->vc_parent = $attributes['vc_version_uid'];

        return $this;
    }

    /**
     * Remove base model attributes
     * Needed for seeders as guards are ignored during seeding
     *
     * @param  array  $attributes
     * @return array
     */
    protected function removeBaseModelAttributes(array $attributes): array
    {
        return Arr::except($attributes, [
            'vc_version_uid',
            'uid',
            'created_at',
            'updated_at'
        ]);
    }

    /**
     * Parent of this version
     *
     * @return HasOne
     */
    public function parent(): HasOne
    {
        $instance = $this->newRelatedInstance(Version::class);
        $instance->setTable($this->getTable());

        $foreignKey = $this->getKeyName();

        $localKey = 'vc_parent';

        return $this->newHasOne($instance->newQuery(), $this, $instance->getTable().'.'.$foreignKey, $localKey);
    }

    /**
     * Restore this version
     *
     * @param  BaseModel  $model
     *
     * @return bool
     */
    public function restore(BaseModel $model)
    {

        $model->fill(
            Arr::except($this->attributes, ['uid', 'model_uid', 'vc_parent', 'created_at'])
        );

        $model->forceFill([
            'vc_version_uid' => $this->attributes['uid']
        ]);

        return tap($model)->save();
    }

    /**
     * User who modified this version
     *
     * @return BelongsTo
     */
    public function modifyingUser(): BelongsTo
    {
        return $this->belongsTo(config('version-control.user'), 'vc_modifier_uid', 'uid')
            ->withDefault(config('version-control.default_modifying_user'));
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

    /**
     * Return only model data
     *
     * @return array
     */
    public function toModelArray()
    {
        return Arr::except($this->attributes, ['uid', 'model_uid', 'vc_parent']);
    }

    /**
     * Perform the actual delete query on this model instance.
     *
     * @return mixed
     */
    protected function performDeleteOnModel()
    {
        throw new ReadOnlyException(__FUNCTION__, get_called_class());
    }

    public function scopeWithoutLatestOrder()
    {

    }
}
