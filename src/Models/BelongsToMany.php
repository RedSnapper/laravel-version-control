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
        $attached = $this->getCurrentlyAttachedPivots();
        $current = $attached->pluck($this->relatedPivotKey)->all();
        $records = $this->formatAttachRecords(
            $this->parseIds($id), $attributes
        );
        foreach ($records as $record) {

            $id = $record[$this->relatedPivotKey];

            if (!in_array($id, $current)) {
                $this->newPivot($record, false)->save();
            } else {
                $this->updateExistingPivot($id,$record,$touch);
            }
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
        $records = $this->getCurrentlyAttachedPivots()->when(count($ids) > 0,function($collection) use($ids){
            return $collection->whereIn($this->relatedPivotKey, $ids);
        });

        foreach ($records as $record) {
            $results += $record->delete();
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
            ->filter->vc_active
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
     * Attach all of the records that aren't in the given current records.
     *
     * @param  array $records
     * @param  array $current
     * @param  bool $touch
     * @return array
     */
    protected function attachNew(array $records, array $current, $touch = true)
    {
        $changes = ['attached' => [], 'updated' => []];

        foreach ($records as $id => $attributes) {
            // If the ID is not in the list of existing pivot IDs, we will insert a new pivot
            // record, otherwise, we will just update this existing record on this joining
            // table, so that the developers will easily update these records pain free.
            if (!in_array($id, $current)) {
                $this->attach($id, $attributes, $touch);

                $changes['attached'][] = $this->castKey($id);
            }

            // Now we'll try to update an existing pivot record with the attributes that were
            // given to the method. If the model is actually updated we will add it to the
            // list of updated pivot records so we return them back out to the consumer.
            elseif (count($attributes) > 0 &&
                $this->updateExistingPivot($id, $attributes, $touch)) {
                $changes['updated'][] = $this->castKey($id);
            }
        }

        return $changes;
    }

    /**
     * Update an existing pivot record on the table.
     *
     * @param  mixed  $id
     * @param  array  $attributes
     * @param  bool   $touch
     * @return int
     */
    public function updateExistingPivot($id, array $attributes, $touch = true)
    {
        $model = $this->currentlyAttached
            ->firstWhere($this->relatedPivotKey,$id)
            ->fill($attributes);
        $model->vc_active = true;

        $updated = $model->isDirty();

        $model->save();

        return (int) $updated;
    }

    /**
     * Get the pivot models that are currently attached.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getCurrentlyAttachedPivots()
    {
        if (!$this->currentlyAttached) {
            $this->currentlyAttached = $this->newPivotQuery()->get()->map(function ($record) {
                return $this->newPivot((array)$record, true);
            });
        }

        return $this->currentlyAttached;
    }

    /**
     * Create a new query builder for the pivot table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function newPivotQuery()
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
