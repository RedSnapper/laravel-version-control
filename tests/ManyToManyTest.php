<?php

namespace Redsnapper\LaravelVersionControl\Tests;

class ManyToManyTest extends Base
{
    protected $model;

    public function setupModel(array $overrides = [])
    {
        $this->model = $this->createRole($overrides);
    }

    /** @test */
    public function can_attach_a_pivot_relation()
    {
        $this->setupModel(['name' => 'Role for bad necks']);
        $permissionA = $this->createPermission(["name" => "can-see-the-ground"]);

        $this->assertFalse($this->model->hasPermission('can-see-the-ground'));

        $this->model->permissions()->attach($permissionA);

        $this->assertTrue($this->model->hasPermission('can-see-the-ground'));
    }

    /** @test */
    public function can_detach_a_pivot_relation()
    {
        $this->setupModel(['name' => 'Role for bad necks']);

        $permissionA = $this->createPermission(["name" => "can-see-the-ground"]);

        $this->model->permissions()->attach($permissionA);

        $this->assertTrue($this->model->hasPermission('can-see-the-ground'));

        // Permissions update when adding new permissions to a role
        $this->model->permissions()->detach($permissionA);
        $this->assertFalse($this->model->hasPermission('can-see-the-ground'));
    }

    /** @test */
    public function can_sync_many_pivot_relations_at_once()
    {
        $this->setupModel(['name' => 'Role for bad necks']);

        $permissionA = $this->createPermission(["name" => "can-see-the-ground"]);
        $permissionB = $this->createPermission(["name" => "can-see-the-sky"]);

        $this->model->permissions()->sync([$permissionA->uid, $permissionB->uid]);

        $this->assertTrue($this->model->hasPermission($permissionA));
        $this->assertTrue($this->model->hasPermission($permissionB));

        $this->model->permissions()->sync($permissionA);

        $this->assertTrue($this->model->hasPermission($permissionA));
        $this->assertFalse($this->model->hasPermission($permissionB));
    }
}
