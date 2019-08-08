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
     * @param  mixed  $id
     * @param  array  $attributes
     * @param  bool  $touch
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

            if (!in_array($record[$this->relatedPivotKey], $current)) {
                $this->newPivot($record, false)->save();
            } else {

                $model = $attached
                  ->firstWhere($this->relatedPivotKey,$record[$this->relatedPivotKey])
                  ->fill($record);

                $model->vc_active = true;
                $model->save();
            }
        }

        //$records = $this->formatAttachRecords(
        //  $this->parseIds($id), $attributes
        //);
        //
        //foreach ($records as $record) {
        //    $this->newPivot($record, false)->save();
        //}
    }

    /**
     * Detach models from the relationship.
     *
     * @param  mixed  $ids
     * @param  bool  $touch
     * @return int
     */
    public function detach($ids = null, $touch = true)
    {
        $ids = $this->parseIds($ids);

        $results = 0;
        $records = $this->getCurrentlyAttachedPivots()->whereIn($this->relatedPivotKey, $ids);

        foreach ($records as $record) {
            $results += $record->delete();
        }

        return $results;
    }

    /**
     * Get the pivot models that are currently attached.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getCurrentlyAttachedPivots()
    {
        return $this->currentlyAttached ?: $this->newPivotQuery()->get()->map(function ($record) {
            return $this->newPivot((array) $record, true);
        });
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
            if($arguments[0] !== 'vc_active'){
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
