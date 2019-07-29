<?php


namespace Redsnapper\LaravelVersionControl\Models;


class BelongsToMany extends \Illuminate\Database\Eloquent\Relations\BelongsToMany
{
    /**
     * Attach a model to the parent.
     *
     * @param  mixed  $id
     * @param  array  $attributes
     * @param  bool   $touch
     * @return void
     */
    public function attach($id, array $attributes = [], $touch = true)
    {
        $id = $this->parseId($id);
        $this->setPivotActiveState($id, 1);
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
        if (! is_null($ids)) {
            $ids = $this->parseIds($ids);

            if (empty($ids)) {
                return 0;
            }

            foreach ($ids as $id) {
                $this->setPivotActiveState($id, 0);
            }

            return count($ids);
        }
    }

    private function setPivotActiveState(string $id, int $active)
    {
        $this->using::updateOrCreate([
            $this->foreignPivotKey => $this->parent->uid,
            $this->relatedPivotKey => $id
        ],[
            'vc_active' => $active
        ]);
    }

    /**
     * Format the sync / toggle record list so that it is keyed by ID.
     * This has to be overwritten to allow for non-numeric ID fields
     *
     * @param  array  $records
     * @return array
     */
    protected function formatRecordsList(array $records)
    {
        return collect($records)->mapWithKeys(function ($attributes, $id) {
            if (! is_array($attributes)) {
                [$id, $attributes] = [$attributes, []];
            }

            if(is_int($id)) {
                return [$id => $attributes];
            }

            return ["$id" => $attributes];

        })->all();
    }
}
