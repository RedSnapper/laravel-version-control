<?php

namespace Redsnapper\LaravelVersionControl\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Arr;
use Illuminate\Support\Pluralizer;
use Redsnapper\LaravelVersionControl\Models\Traits\NoDeletesModel;
use Redsnapper\LaravelVersionControl\Models\Traits\ReadOnlyModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Redsnapper\LaravelVersionControl\Scopes\SoftDeletingScope;

class BaseModel extends Model
{
    protected $primaryKey = 'uid';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    public $incrementing = false;

    use ReadOnlyModel,
      NoDeletesModel;

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new SoftDeletingScope);
    }

    /**
     * Create a new version
     *
     * @return bool
     */
    protected function createVersion(): bool
    {
        if (!$this->isDirty() && $this->exists) {
            return true;
        }

        $version = $this->getVersionInstance();

        if (!$this->exists) {
            $version->createFromNew($this->attributes);
        } else {
            $version->createFromExisting($this->attributes);
        }

        if ($version->save()) {
            $this->uid = $version->model_uid;
            $this->vc_version_uid = $version->uid;
            $this->vc_active = $version->vc_active;

            return true;
        }

        return false;
    }

    /**
     * Name of the versions table
     *
     * @return string
     */
    public function getVersionsTable(): string
    {
        return Pluralizer::singular($this->getTable())."_versions";
    }

    /**
     * Get version model with table set
     *
     * @return Version
     */
    private function getVersionInstance(): Version
    {
        $class = config('version-control.version_model');
        $versionClass = new $class;
        return $versionClass->setTable($this->getVersionsTable());
    }

    /**
     * Get all versions
     *
     * @return HasMany
     */
    public function versions(): HasMany
    {
        $instance = $this->getVersionInstance();

        $foreignKey = "model_uid";
        $localKey = "uid";

        return $this->newHasMany(
          $instance->newQuery(), $this, $instance->getTable().'.'.$foreignKey, $localKey
        );
    }

    /**
     * Get the current version
     *
     * @return HasOne
     */
    public function currentVersion(): HasOne
    {
        $instance = $this->getVersionInstance();

        $foreignKey = "uid";
        $localKey = "vc_version_uid";

        return $this->newHasOne($instance->newQuery(), $this, $instance->getTable().'.'.$foreignKey, $localKey);
    }

    /**
     * Is the model active
     *
     * @param $value
     * @return bool
     */
    public function getVcActiveAttribute($value)
    {
        return (bool) $value;
    }

    /**
     * Perform the actual delete query on this model instance.
     *
     * @return mixed
     */
    protected function performDeleteOnModel()
    {
        $this->vc_active = false;
        $this->save();

        $this->exists = false;
    }

    /**
     * Perform a model insert operation.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return bool
     */
    protected function performInsert(Builder $query)
    {
        if ($this->fireModelEvent('creating') === false) {
            return false;
        }

        if(!$this->createVersion()){
            return false;
        }

        // First we'll need to create a fresh query instance and touch the creation and
        // update timestamps on this model, which are maintained by us for developer
        // convenience. After, we will just continue saving these model instances.
        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }

        // If the model has an incrementing key, we can use the "insertGetId" method on
        // the query builder, which will give us back the final inserted ID for this
        // table from the database. Not all tables have to be incrementing though.
        $attributes = $this->getAttributes();

        if ($this->getIncrementing()) {
            $this->insertAndSetId($query, $attributes);
        }

        // If the table isn't incrementing we'll simply insert these attributes as they
        // are. These attribute arrays must contain an "id" column previously placed
        // there by the developer as the manually determined key for these models.
        else {
            if (empty($attributes)) {
                return true;
            }

            $query->insert($attributes);
        }

        // We will go ahead and set the exists property to true, so that it is set when
        // the created event is fired, just in case the developer tries to update it
        // during the event. This will allow them to do so and run an update here.
        $this->exists = true;

        $this->wasRecentlyCreated = true;

        $this->fireModelEvent('created', false);

        return true;
    }

    /**
     * Perform a model update operation.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return bool
     */
    protected function performUpdate(Builder $query)
    {
        // If the updating event returns false, we will cancel the update operation so
        // developers can hook Validation systems into their models and cancel this
        // operation if the model does not pass validation. Otherwise, we update.
        if ($this->fireModelEvent('updating') === false) {
            return false;
        }

        if(!$this->createVersion()){
            return false;
        }

        // First we need to create a fresh query instance and touch the creation and
        // update timestamp on the model which are maintained by us for developer
        // convenience. Then we will just continue saving the model instances.
        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }

        // Once we have run the update operation, we will fire the "updated" event for
        // this model instance. This will allow developers to hook into these after
        // models are updated, giving them a chance to do any special processing.
        $dirty = $this->getDirty();

        if (count($dirty) > 0) {
            $this->setKeysForSaveQuery($query)->update($dirty);

            $this->syncChanges();

            $this->fireModelEvent('updated', false);
        }

        return true;
    }

    /**
     * Compares the version number on this key row vs latest versioned table row
     *
     * @return bool
     */
    public function validateVersion(): bool
    {
        return $this->versions()->latest()->first()->uid === $this->vc_version_uid;
    }

    /**
     * Compares the values in this key table row to the values in the latest versioned table row and validates it is
     * equal
     *
     * @return bool
     */
    public function validateData(): bool
    {
        $me = collect(Arr::except($this->toArray(), ['uid', 'vc_version_uid', 'updated_at']));

        $difference = $me->diffAssoc($this->versions()->latest()->first()->toArray());

        return $difference->isEmpty();
    }

    /**
     * Restore to this version
     *
     * @param  string|Version  $version
     * @return BaseModel
     */
    public function restore($version)
    {
        if (is_string($version)) {
            $instance = $this->getVersionInstance();
            $version = $instance->findOrFail($version);
        }

        return $version->restore($this);
    }

    /**
     * Anonymize field data
     *
     * @param  array  $fields
     */
    public function anonymize(array $fields)
    {

        // Create a record to anonymize data
        $this->update($fields);

        // Update all the version data
        $this->versions()->update($fields);

    }


    /**
     * Instantiate a new BelongsToMany relationship.
     *
     * @param  Builder  $query
     * @param  Model  $parent
     * @param  string  $table
     * @param  string  $foreignPivotKey
     * @param  string  $relatedPivotKey
     * @param  string  $parentKey
     * @param  string  $relatedKey
     * @param  string  $relationName
     * @return BelongsToMany
     */
    protected function newBelongsToMany(
      Builder $query,
      Model $parent,
      $table,
      $foreignPivotKey,
      $relatedPivotKey,
      $parentKey,
      $relatedKey,
      $relationName = null
    ) {
        return (new BelongsToMany($query, $parent, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey,
          $relationName))->wherePivot('vc_active', 1);
    }

    /**
     * Check if the model is deleted or not
     *
     * @return bool
     */
    public function trashed()
    {
        return !$this->vc_active;
    }
}
