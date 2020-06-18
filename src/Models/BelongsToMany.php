<?php

namespace Redsnapper\LaravelVersionControl\Models;

use Illuminate\Database\Eloquent\Collection;

class BelongsToMany extends \Illuminate\Database\Eloquent\Relations\BelongsToMany
{

    protected $using = BasePivotModel::class;
    //
    /**
     * Indicates if timestamps are available on the pivot table.
     *
     * @var bool
     */
    public $withTimestamps = true;

    /**
     * The pivot table columns to retrieve.
     *
     * @var array
     */
    protected $pivotColumns = ['created_at', 'updated_at', 'uid', 'vc_version_uid'];

    /**
     * The cached copy of the currently attached pivot models.
     *
     * @var Collection
     */
    private $currentlyAttached;

    /**
     * The cached copy of any existing pivots(maybe be inactive).
     *
     * @var Collection
     */
    private $existingPivots;


    /**
     * Attach a model to the parent.
     *
     * @param  mixed $id
     * @param  array $attributes
     * @param  bool $touch
     * @return void
     */
    public function attach($id, array $attributes = [], $touch = true)
    {

        // First we need to get any attached pivots as they may exist in the database but have been set to inactive
        // We will check if they exist and update them if they do otherwise we will create a new record
        $existing = $this->getExistingPivots();
        $current = $existing->pluck($this->relatedPivotKey)->all();
        $records = $this->formatAttachRecords(
            $this->parseIds($id), $attributes
        );
        foreach ($records as $record) {

            $id = $record[$this->relatedPivotKey];

            if (!in_array($id, $current)) {
                $this->newPivot($record, false)->save();
            } else {
                $this->updateExistingPivot($id, $record, $touch);
            }
        }

        if ($touch) {
            $this->touchIfTouching();
        }

    }

    /**
     * Detach models from the relationship.
     *
     * @param  mixed $ids
     * @param  bool $touch
     * @return int
     */
    public function detach($ids = null, $touch = true)
    {
        $ids = $this->parseIds($ids);

        $results = 0;
        $records = $this->getExistingPivots()->when(count($ids) > 0, function ($collection) use ($ids) {
            return $collection->whereIn($this->relatedPivotKey, $ids);
        });

        foreach ($records as $record) {
            $results += $record->delete();
        }

        if ($touch) {
            $this->touchIfTouching();
        }

        return $results;
    }

    /**
     * Sync the intermediate tables with a list of IDs or collection of models.
     *
     * @param  \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Model|array $ids
     * @param  bool $detaching
     * @return array
     */
    public function sync($ids, $detaching = true)
    {
        $changes = [
            'attached' => [],
            'detached' => [],
            'updated' => [],
        ];

        // First we need to attach any of the associated models that are not currently
        // in this joining table. We'll spin through the given IDs, checking to see
        // if they exist in the array of current ones, and if not we will insert.
        $current = $this->getCurrentlyAttachedPivots()
            ->pluck($this->relatedPivotKey)->all();

        $detach = array_diff($current, array_keys(
            $records = $this->formatRecordsList($this->parseIds($ids))
        ));

        // Next, we will take the differences of the currents and given IDs and detach
        // all of the entities that exist in the "current" array but are not in the
        // array of the new IDs given to the method which will complete the sync.
        if ($detaching && count($detach) > 0) {
            $this->detach($detach);

            $changes['detached'] = $this->castKeys($detach);
        }

        // Now we are finally ready to attach the new records. Note that we'll disable
        // touching until after the entire operation is complete so we don't fire a
        // ton of touch operations until we are totally done syncing the records.
        $changes = array_merge(
            $changes, $this->attachNew($records, $current, false)
        );

        // Once we have finished attaching or detaching the records, we will see if we
        // have done any attaching or detaching, and if we have we will touch these
        // relationships if they are configured to touch on any database updates.
        if (count($changes['attached']) ||
            count($changes['updated'])) {
            $this->touchIfTouching();
        }

        return $changes;
    }

    /**
     * Toggles a model (or models) from the parent.
     *
     * Each existing model is detached, and non existing ones are attached.
     *
     * @param  mixed $ids
     * @param  bool $touch
     * @return array
     */
    public function toggle($ids, $touch = true)
    {
        $changes = [
            'attached' => [],
            'detached' => [],
        ];

        $records = $this->formatRecordsList($this->parseIds($ids));

        $current = $this->getCurrentlyAttachedPivots();

        // Next, we will determine which IDs should get removed from the join table by
        // checking which of the given ID/records is in the list of current records
        // and removing all of those rows from this "intermediate" joining table.
        $detach = array_values(array_intersect(
            $current->pluck($this->relatedPivotKey)->all(),
            array_keys($records)
        ));

        if (count($detach) > 0) {
            $this->detach($detach, false);

            $changes['detached'] = $this->castKeys($detach);
        }

        // Finally, for all of the records which were not "detached", we'll attach the
        // records into the intermediate table. Then, we will add those attaches to
        // this change list and get ready to return these results to the callers.
        $attach = array_diff_key($records, array_flip($detach));

        if (count($attach) > 0) {
            $this->attach($attach, [], false);

            $changes['attached'] = array_keys($attach);
        }

        // Once we have finished attaching or detaching the records, we will see if we
        // have done any attaching or detaching, and if we have we will touch these
        // relationships if they are configured to touch on any database updates.
        if ($touch && (count($changes['attached']) ||
                count($changes['detached']))) {
            $this->touchIfTouching();
        }

        return $changes;
    }

    /**
     *
     * /**
     * Update an existing pivot record on the table.
     *
     * @param  mixed $id
     * @param  array $attributes
     * @param  bool $touch
     * @return int
     */
    public function updateExistingPivot($id, array $attributes, $touch = true)
    {
        $model = $this->getExistingPivots()
            ->firstWhere($this->relatedPivotKey, $id)
            ->fill($attributes);
        $model->vc_active = true;

        $updated = $model->isDirty();

        $model->save();

        if ($touch) {
            $this->touchIfTouching();
        }

        return (int)$updated;
    }

    /**
     * Get the pivot models that are currently attached.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getCurrentlyAttachedPivots()
    {
        if (!$this->currentlyAttached) {
            $this->currentlyAttached = $this->getExistingPivots()->filter->vc_active;
        }

        return $this->currentlyAttached;
    }

    /**
     * Get the pivot models that are currently attached.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getExistingPivots()
    {
        if (!$this->existingPivots) {
            $this->existingPivots = $this->newPivotQuery()->get()->map(function ($record) {
                return $this->newPivot((array)$record, true);
            });
        }

        return $this->existingPivots;
    }

    /**
     * Create a new query builder for the pivot table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function newPivotQuery()
    {
        $query = $this->newPivotStatement();

        foreach ($this->pivotWheres as $arguments) {
            if ($arguments[0] !== 'vc_active') {
                call_user_func_array([$query, 'where'], $arguments);
            }
        }

        foreach ($this->pivotWhereIns as $arguments) {
            call_user_func_array([$query, 'whereIn'], $arguments);
        }

        return $query->where($this->foreignPivotKey, $this->parent->{$this->parentKey});
    }

    /**
     * Get the class being used for pivot models.
     *
     * @return string
     */
    public function getPivotClass()
    {
        return $this->using ?? BasePivotModel::class;
    }

}
