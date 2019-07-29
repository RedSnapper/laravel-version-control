<?php

namespace Redsnapper\LaravelVersionControl\Models;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Pluralizer;
use Redsnapper\LaravelVersionControl\Models\Traits\ActiveOnlyModel;
use Redsnapper\LaravelVersionControl\Models\Traits\NoDeletesModel;
use Redsnapper\LaravelVersionControl\Models\Traits\ReadOnlyModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Class BaseModel
 *
 * @property string $uid
 * @property int $vc_version
 * @property int $vc_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class BaseModel extends Model
{
    protected $primaryKey = 'uid';
    public $incrementing = false;

    use ActiveOnlyModel,
      ReadOnlyModel,
      NoDeletesModel;

    public static function boot()
    {
        parent::boot();

        static::saving(function (BaseModel $model) {
            return $model->createVersion();
        });

        static::deleting(function (BaseModel $model) {
            return $model->createDeleteVersion();
        });
    }

    protected function createVersion(): bool
    {
        $version = $this->getVersionInstance();
        $version->fill($this->attributes);

        if (!$this->exists) {
            $uid = Str::uuid();
            $version->uid = $uid;
        } else {
            $version->uid = $this->uid;
            // If we're saving an existing record, then the parent of version must be set before we try to save it... the VC version will be incremented on save
            $version->vc_parent = $this->vc_version;
        }

        if ($version->save()) {
            $this->uid = $version->uid;
            $this->vc_version = $version->vc_version;
            $this->vc_active = $version->vc_active;

            return true;
        }

        return false;
    }

    /**
     * Gets the restore point based on key and version passed. Replicates it as a new version & sets the parent to
     * restore point
     *
     * @param $version
     * @return BaseModel
     * @throws Exception
     */
    public function restore($version): BaseModel
    {
        // We do this to check the restore point is valid
        try {
            $restorePoint = $this->versions()
                ->where('vc_version', $version)
                ->firstOrFail();
        } catch (Exception $e) {
            throw new Exception('The version you have attempted to restore to does not exist');
        }

        $this->fill($restorePoint->toArray());
        $this->save();

        return $this;
    }

    protected function createDeleteVersion()
    {
        $version = $this->getVersionInstance();
        $version->fill($this->attributes);
        $version->vc_active = false;

        if ($version->save()) {
            $this->vc_version = $version->vc_version;
        }
    }

    /**
     * @return string
     */
    public function getVersionsTable(): string
    {
        return Pluralizer::singular($this->getTable())."_versions";
    }

    /**
     * @return Versioned
     */
    private function getVersionInstance(): Versioned
    {
        $versionClass = new Versioned();
        return $versionClass->setTable($this->getVersionsTable());
    }

    /**
     * Fetches the version history for the key table model. For this to work, table and model naming convention must be
     * kept to (key table = users, version table = user_versions)
     *
     * @return HasMany
     */
    public function versions(): HasMany
    {
        $instance = $this->getVersionInstance();

        $foreignKey = "uid";
        $localKey = "uid";

        return $this->newHasMany(
          $instance->newQuery(), $this, $instance->getTable().'.'.$foreignKey, $localKey
        );
    }

    protected function performDeleteOnModel()
    {

        $query = $this->setKeysForSaveQuery($this->newModelQuery());
        $query->update(['vc_version' => $this->vc_version, 'vc_active' => false]);

        $this->exists = false;
    }

    /**
     * Compares the version number on this key row vs latest versioned table row
     *
     * @return bool
     */
    public function validateVersion(): bool
    {
        $latest = $this->versions()->latest()->first();
        return (int)$this->vc_version === (int)$latest->vc_version;
    }

    /**
     * Compares the values in this key table row to the values in the latest versioned table row and validates it is
     * equal
     *
     * @return bool
     */
    public function validateData(): bool
    {
        $me = collect($this->toArray());
        $difference = $me->diff($this->versions()->latest()->first()->toArray());
        return (empty($difference->all())) ? true : false;
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    protected function newBelongsToMany(Builder $query, Model $parent, $table, $foreignPivotKey, $relatedPivotKey,
        $parentKey, $relatedKey, $relationName = null)
    {
        return new BelongsToMany($query, $parent, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relationName);
    }
}
