<?php

namespace Redsnapper\LaravelVersionControl\Tests;

use Illuminate\Support\Carbon;
use Redsnapper\LaravelVersionControl\Tests\Fixtures\Models\Permission;
use Redsnapper\LaravelVersionControl\Tests\Fixtures\Models\PermissionRole;
use Redsnapper\LaravelVersionControl\Tests\Fixtures\Models\Role;

class ManyToManyTest extends Base
{
    /** @test */
    public function can_attach_a_pivot_relation_using_a_model()
    {
        $date = Carbon::create(2019, 1, 31);
        Carbon::setTestNow($date);

        $role = factory(Role::class)->create();
        $permissionA = factory(Permission::class)->create();

        $role->permissions()->attach($permissionA);

        $this->assertCount(1,$role->permissions);
        $this->assertTrue($role->permissions->first()->is($permissionA));
        $this->assertEquals($date,$role->permissions->first()->pivot->created_at);

    }

    /** @test */
    public function can_attach_a_pivot_relation_using_a_model_with_pivot_data()
    {
        $role = factory(Role::class)->create();
        $permission = factory(Permission::class)->create();

        $role->permissions()->attach($permission,['region' => 'foo']);

        $attached = $role->permissions->first();
        $this->assertTrue($attached->is($permission));
        $this->assertEquals('foo',$attached->pivot->region);

    }

    /** @test */
    public function can_attach_multiple_using_attach()
    {
        $role = factory(Role::class)->create();
        $permissionA = factory(Permission::class)->create();
        $permissionB = factory(Permission::class)->create();

        $role->permissions()->attach([
          $permissionA->uid => ['region'=>'A'],
          $permissionB->uid => ['region'=>'B'],
        ]);

        $permissions = $role->permissions()->orderBy('region')->get();

        $this->assertCount(2,$permissions);
        $this->assertEquals('A',$permissions->first()->pivot->region);

    }

    /** @test */
    public function can_attach_using_custom_pivot_model()
    {
        $role = factory(Role::class)->create();
        $permission = factory(Permission::class)->create();

        $permission->roles()->attach($role,['region'=>'foo']);

        $this->assertEquals(PermissionRole::class,$permission->roles()->getPivotClass());
        $this->assertTrue($permission->roles->first()->is($role));

    }

    /** @test */
    public function versions_work_correctly_when_attaching()
    {
        $role = factory(Role::class)->create();
        $permissionA = factory(Permission::class)->create();

        $role->permissions()->attach($permissionA,['region'=>'foo']);
        $this->assertEquals("foo",$role->permissions->first()->pivot->currentVersion->region);
        $this->assertEquals("foo",$role->permissions->first()->pivot->versions->first()->region);
    }

    /** @test */
    public function can_detach_a_pivot_relation()
    {
        $role = factory(Role::class)->create();
        $permissionA = factory(Permission::class)->create();
        $permissionB = factory(Permission::class)->create();

        $role->permissions()->attach($permissionA);
        $role->permissions()->attach($permissionB);
        $this->assertCount(2,$role->permissions);
        $permissionCount = $role->permissions()->detach($permissionA);
        $this->assertDatabaseHas('permission_role',[
          'permission_uid'=>$permissionA->uid,
          'role_uid'=>$role->uid,
          'vc_active'=>false
        ]);
        $this->assertEquals(1,$permissionCount);
        $this->assertCount(1,$role->fresh()->permissions);
        $this->assertCount(2,$role->permissions->first()->pivot->versions);
    }

    /** @test */
    public function can_detach_multiple_relations()
    {
        $role = factory(Role::class)->create();
        $permissionA = factory(Permission::class)->create();
        $permissionB = factory(Permission::class)->create();

        $role->permissions()->attach($permissionA);
        $role->permissions()->attach($permissionB);

        $permissionCount = $role->permissions()->detach([$permissionA->getKey(),$permissionB->getKey()]);
        $this->assertEquals(2,$permissionCount);
        $this->assertCount(0,$role->fresh()->permissions);

    }

    /** @test */
    public function can_reattach_a_deleted_relation()
    {
        $role = factory(Role::class)->create();
        $permissionA = factory(Permission::class)->create();
        $role->permissions()->attach($permissionA);
        $role->permissions()->detach($permissionA);
        $role->permissions()->attach($permissionA);

        $this->assertCount(1,$role->permissions);
        $this->assertCount(3,$role->permissions->first()->pivot->versions);

        $role->permissions()->attach($permissionA,['region'=>'foo']);
        $this->assertCount(1,$role->refresh()->permissions);
        $this->assertCount(4,$role->permissions->first()->pivot->versions);

    }

    // TODO Dates and touch


    ///** @test */
    //public function can_sync_many_pivot_relations_at_once()
    //{
    //    $this->setupModel(['name' => 'Role for bad necks']);
    //
    //    $permissionA = $this->createPermission(["name" => "can-see-the-ground"]);
    //    $permissionB = $this->createPermission(["name" => "can-see-the-sky"]);
    //
    //    $this->model->permissions()->sync([$permissionA->uid, $permissionB->uid]);
    //
    //    $this->assertTrue($this->model->hasPermission($permissionA));
    //    $this->assertTrue($this->model->hasPermission($permissionB));
    //
    //    $this->model->permissions()->sync($permissionA);
    //
    //    $this->assertTrue($this->model->hasPermission($permissionA));
    //    $this->assertFalse($this->model->hasPermission($permissionB));
    //}
}
