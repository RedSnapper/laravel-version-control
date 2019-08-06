<?php

namespace Redsnapper\LaravelVersionControl\Models;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Arr;
use Illuminate\Support\Pluralizer;
use Redsnapper\LaravelVersionControl\Models\Traits\ActiveOnlyModel;
use Redsnapper\LaravelVersionControl\Models\Traits\NoDeletesModel;
use Redsnapper\LaravelVersionControl\Models\Traits\ReadOnlyModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Redsnapper\LaravelVersionControl\Scopes\ActiveScope;

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

    use ReadOnlyModel,
      NoDeletesModel;

    public static function boot()
    {
        parent::boot();

        static::saving(function (BaseModel $model) {
            return $model->createVersion();
        });

        static::addGlobalScope(new ActiveScope());
    }

    /**
     * Create a new version
     *
     * @return bool
     */
    protected function createVersion(): bool
    {
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

        $foreignKey = "model_uid";
        $localKey = "uid";

        return $this->newHasMany(
          $instance->newQuery(), $this, $instance->getTable().'.'.$foreignKey, $localKey
        );
    }


    /**
     * Fetches the version history for the key table model. For this to work, table and model naming convention must be
     * kept to (key table = users, version table = user_versions)
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

    protected function performDeleteOnModel()
    {
        $this->vc_active = false;
        $this->save();

        $this->exists = false;
    }

    /**
     * Compares the version number on this key row vs latest versioned table row
     *
     * @return bool
     */
    public function validateVersion(): bool
    {
        return $this->versions()->latest()->first()->uid === (string) $this->vc_version_uid;
    }

    /**
     * Compares the values in this key table row to the values in the latest versioned table row and validates it is
     * equal
     *
     * @return bool
     */
    public function validateData(): bool
    {
        $me = collect(Arr::except($this->toArray(),['uid','vc_version_uid']));

        $difference = $me->diffAssoc($this->versions()->latest()->first()->toModelArray());

        return $difference->isEmpty();
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
        return new BelongsToMany($query, $parent, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey,
          $relationName);
    }
}
