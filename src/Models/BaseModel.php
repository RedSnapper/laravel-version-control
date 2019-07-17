<?php

namespace Redsnapper\LaravelVersionControl\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Redsnapper\LaravelVersionControl\Exceptions\ReadOnlyException;
use Redsnapper\LaravelVersionControl\Models\Traits\ActiveOnlyModel;
use Redsnapper\LaravelVersionControl\Models\Traits\NoDeletesModel;
use Redsnapper\LaravelVersionControl\Models\Traits\NoUpdatesModel;
use Redsnapper\LaravelVersionControl\Models\Traits\ReadOnlyModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Class BaseModel
 *
 * @property string $unique_key
 * @property int $vc_version
 * @property int $vc_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class BaseModel extends Model
{
    protected $primaryKey = 'unique_key';
    public $incrementing = false;

    use ActiveOnlyModel,
      ReadOnlyModel,
      NoDeletesModel;

    public static function boot()
    {
        parent::boot();

        static::creating(function (BaseModel $model) {
            return $model->createVersion();
        });

        static::updating(function (BaseModel $model) {
            return $model->createVersion();
        });

        static::deleting(function (BaseModel $model) {
            return $model->createDeleteVersion();
        });
    }

    protected function createVersion(): bool
    {
        $version = $this->getVersionInstance();

        if (!$this->exists) {
            $uid = Str::uuid();
            $version->unique_key = $uid;
        }

        $version->fill($this->attributes);

        if ($version->save()) {
            $this->unique_key = $version->unique_key;
            $this->vc_version = $version->vc_version;
            $this->vc_active = $version->vc_active;
            return true;
        }

        return false;
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
        return $this->getTable()."_versions";
    }

    /**
     * @param  string  $versionsTable
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

        $foreignKey = "unique_key";
        $localKey = "unique_key";

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
        return $this->vc_version === $latest->vc_version;
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
     * Gets the restore point based on key and version passed. Replicates it as a new version & sets the parent to
     * restore point
     *
     * @param  string  $key
     * @param  int|string  $version
     * @return Versioned
     */
    public static function restore(string $key, $version): Versioned
    {
        $versionsTable = self::getVersionTable();
        $restorePoint = self::getVersionInstance($versionsTable);

        $restorePoint = $restorePoint->where('unique_key', $key)
          ->where('vc_version', $version)
          ->firstOrFail();

        $new = $restorePoint->replicate();
        $new->setTable($versionsTable);
        $new->setBaseModel(get_called_class());
        $new->unique_key = $restorePoint->unique_key;
        $new->vc_parent = $restorePoint->vc_version;
        $new->save();

        return $new;
    }

    ///**
    // * To delete something, we replicate the current instance, then enter a new one with only the active key different
    // * to signify deletion The creating event takes care of still incrementing the version, branch etc and created
    // * takes care of the key table
    // *
    // * @return bool
    // */
    //public function delete(): bool
    //{
    //    $latest = $this->versions()->latest()->first();
    //
    //    /** @var Versioned $delete */
    //    $delete = $latest->replicate();
    //    $delete->setTable($this->versionsTable);
    //    $delete->setBaseModel(get_class($this));
    //    $delete->unique_key = $this->unique_key;
    //    $delete->vc_active = 0;
    //    $delete->save();
    //
    //    return true;
    //}

    /**
     * @param  string  $key1
     * @param  string  $key2
     * @param  BaseModel  $pivot
     * @return BaseModel
     */
    public function attach(string $key1, string $key2, BaseModel $pivot): BaseModel
    {
        $existing = $pivot->where($pivot->key1, $key1)
          ->where($pivot->key2, $key2)
          ->first();

        $versionsTable = $pivot::getVersionTable();

        if (!empty($existing)) {
            $model = self::replicateVersion($versionsTable, $existing->unique_key);
        } else {
            $model = self::getNewVersion($versionsTable);
        }

        // Now populate new data
        $model->fill([$pivot->key1 => $key1, $pivot->key2 => $key2, "vc_active" => 1]);
        $model->setBaseModel(get_class($pivot));
        $model->save();

        // Key table model will now exist due to versioned creating event hook (see Versioned.php)
        return get_class($pivot)::where('unique_key', $model->unique_key)->first();
    }

    /**
     * Starts the process of creating a new version by replicating the current one
     *
     * @param  string  $versionsTable
     * @param $key
     * @return Versioned
     */
    private static function replicateVersion(string $versionsTable, $key): Versioned
    {
        $previous = self::getVersionInstance($versionsTable)
          ->where('unique_key', $key)
          ->orderBy('vc_version', 'desc')
          ->orderBy('vc_branch', 'desc')
          ->first(); // Fetch the most recent version

        $model = $previous->replicate([
          'vc_version', 'vc_parent', 'vc_branch'
        ]); // And use it as the basis for our new version
        $model->setTable($versionsTable);

        if (property_exists($previous, "password")) {
            $model->password = $previous->password;
        }

        $model->unique_key = $key;

        return $model;
    }

    /**
     * For instances where a totally new record is being made, grab a new version with uuid
     *
     * @param $versionsTable
     * @return Versioned
     */
    private static function getNewVersion($versionsTable): Versioned
    {
        $model = self::getVersionInstance($versionsTable);
        $model->unique_key = Str::uuid();

        return $model;
    }
}
