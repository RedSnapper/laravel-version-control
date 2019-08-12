<?php

namespace Redsnapper\LaravelVersionControl\Tests;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Redsnapper\LaravelVersionControl\Tests\Fixtures\Models\Permission;
use Redsnapper\LaravelVersionControl\Tests\Fixtures\Models\PermissionRole;
use Redsnapper\LaravelVersionControl\Tests\Fixtures\Models\Role;

class ManyToManyTest extends Base
{
    private $assertEquals;

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

        $role->permissions()->attach($permission,['flag' => 'foo']);

        $attached = $role->permissions->first();
        $this->assertTrue($attached->is($permission));
        $this->assertEquals('foo',$attached->pivot->flag);

    }

    /** @test */
    public function can_attach_multiple_using_attach()
    {
        $role = factory(Role::class)->create();
        $permissionA = factory(Permission::class)->create();
        $permissionB = factory(Permission::class)->create();

        $role->permissions()->attach([
          $permissionA->uid => ['flag'=>'A'],
          $permissionB->uid => ['flag'=>'B'],
        ]);

        $permissions = $role->permissions()->orderBy('flag')->get();

        $this->assertCount(2,$permissions);
        $this->assertEquals('A',$permissions->first()->pivot->flag);

    }

    /** @test */
    public function can_attach_using_custom_pivot_model()
    {
        $role = factory(Role::class)->create();
        $permission = factory(Permission::class)->create();

        $permission->roles()->attach($role,['flag'=>'foo']);

        $this->assertEquals(PermissionRole::class,$permission->roles()->getPivotClass());
        $this->assertTrue($permission->roles->first()->is($role));

    }

    /** @test */
    public function versions_work_correctly_when_attaching()
    {
        $role = factory(Role::class)->create();
        $permissionA = factory(Permission::class)->create();

        $role->permissions()->attach($permissionA,['flag'=>'foo']);
        $this->assertEquals("foo",$role->permissions->first()->pivot->currentVersion->flag);
        $this->assertEquals("foo",$role->permissions->first()->pivot->versions->first()->flag);
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
    public function calling_detach_without_arguments_detaches_all()
    {
        $role = factory(Role::class)->create();
        $permissionA = factory(Permission::class)->create();
        $permissionB = factory(Permission::class)->create();

        $role->permissions()->attach($permissionA);
        $role->permissions()->attach($permissionB);
        $this->assertCount(2,$role->permissions);

        $role->permissions()->detach();

        $this->assertCount(0,$role->refresh()->permissions);


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

        $role->permissions()->attach($permissionA,['flag'=>'foo']);
        $this->assertCount(1,$role->refresh()->permissions);
        $this->assertCount(4,$role->permissions->first()->pivot->versions);

    }

    // TODO Dates and touch


    /** @test */
    public function can_sync_pivot_relations()
    {
        $role = factory(Role::class)->create();
        $permissionA = factory(Permission::class)->create();
        $permissionB = factory(Permission::class)->create();

        // Add permission A and B
        $results = $role->permissions()->sync([$permissionA->getKey(),$permissionB->getKey()]);

        $this->assertCount(2,$results['attached']);
        $this->assertContains($permissionA->getKey(),$results['attached']);
        $this->assertContains($permissionB->getKey(),$results['attached']);
        $this->assertCount(2,$role->permissions);

        // Remove permission B
        $results = $role->permissions()->sync($permissionA);

        $this->assertContains($permissionB->getKey(),$results['detached']);
        $this->assertCount(1,$role->fresh()->permissions);

        // Update permission A
        $results = $role->permissions()->sync([$permissionA->getKey() =>['flag'=>'foo']]);

        $this->assertCount(0,$results['detached']);
        $this->assertContains($permissionA->getKey(),$results['updated']);

        $this->assertCount(1,$role->refresh()->permissions);
        $this->assertEquals('foo',$role->permissions->first()->pivot->flag);
        $this->assertCount(2,$role->permissions->first()->pivot->versions);

    }

    /** @test */
    public function a_reattached_pivot_model_returns_as_attached()
    {
        $role = factory(Role::class)->create();
        $permission = factory(Permission::class)->create();

        $role->permissions()->sync($permission);
        $role->permissions()->detach($permission);
        $results = $role->permissions()->sync([$permission->getKey()=>['flag'=>'foo']]);
        $this->assertCount(1,$results['attached']);
        $this->assertEquals('foo',$role->permissions->first()->pivot->flag);

    }

    /** @test */
    public function a_sync_where_nothing_changes_results_in_no_change()
    {
        $role = factory(Role::class)->create();
        $permission = factory(Permission::class)->create();

        $role->permissions()->sync($permission);
        $results = $role->permissions()->sync($permission);

        $this->assertCount(0,$results['attached']);
        $this->assertCount(0,$results['updated']);
        $this->assertCount(1,$role->permissions->first()->pivot->versions);
    }

    /** @test */
    public function can_sync_without_detaching()
    {

        $role = factory(Role::class)->create();
        $permissionA = factory(Permission::class)->create();
        $permissionB = factory(Permission::class)->create();

        $role->permissions()->sync($permissionA);
        $role->permissions()->syncWithoutDetaching($permissionB);

        $this->assertCount(2,$role->permissions);

    }

    /** @test */
    public function can_update_existing_pivot()
    {
        $role = factory(Role::class)->create();
        $permission = factory(Permission::class)->create();

        $role->permissions()->attach($permission,['flag'=>'foo']);
        $role->permissions()->updateExistingPivot($permission->getKey(),['flag'=>'bar']);
        $this->assertEquals('bar',$role->permissions->first()->pivot->flag);
        $this->assertCount(2,$role->permissions->first()->pivot->versions);
    }

    /** @test */
    public function use_the_save_of_the_pivot_relationship()
    {
        $role = factory(Role::class)->create();
        $permission = factory(Permission::class)->create();

        $role->permissions()->save($permission,['flag'=>'foo']);

        $this->assertEquals('foo',$role->permissions->first()->pivot->flag);
    }

    /** @test */
    public function test_toggle_method()
    {
        $role = factory(Role::class)->create();
        $permissionA = factory(Permission::class)->create();
        $permissionB = factory(Permission::class)->create();

        $role->permissions()->toggle([$permissionA->uid]);

        $this->assertEquals(
            Permission::whereIn('uid', [$permissionA->uid])->pluck('name'),
            $role->load('permissions')->permissions->pluck('name')
        );

        $role->permissions()->toggle([$permissionB->getKey(), $permissionA->getKey()]);
        $this->assertEquals(
            Permission::whereIn('uid', [$permissionB->uid])->pluck('name'),
            $role->load('permissions')->permissions->pluck('name')
        );

        $role->permissions()->toggle([$permissionB->getKey(), $permissionA->getKey() => ['flag' => 'foo']]);

        $this->assertEquals(
            Permission::whereIn('uid', [$permissionA->getKey()])->pluck('name'),
            $role->load('permissions')->permissions->pluck('name')
        );
        $this->assertEquals('foo', $role->permissions[0]->pivot->flag);
        $this->assertCount(3,$role->permissions[0]->pivot->versions);

    }

    /** @test */
    public function test_first_method()
    {
        $role = factory(Role::class)->create();
        $permission = factory(Permission::class)->create();
        $role->permissions()->attach(Permission::all());
        $this->assertEquals($permission->name, $role->permissions()->first()->name);
    }

    /** @test */
    public function test_firstOrFail_method()
    {
        $this->expectException(ModelNotFoundException::class);
        $role = factory(Role::class)->create();
        $role->permissions()->firstOrFail(['uid' => 10]);
    }

    /** @test */
    public function test_find_method()
    {
        $role = factory(Role::class)->create();
        $permissionA = factory(Permission::class)->create();
        $permissionB = factory(Permission::class)->create();
        $role->permissions()->attach(Permission::all());
        $this->assertEquals($permissionB->name, $role->permissions()->find($permissionB->uid)->name);
        $this->assertCount(2, $role->permissions()->findMany([$permissionA->uid, $permissionB->uid]));
    }

    /** @test */
    public function test_findOrFail_method()
    {
        $this->expectException(ModelNotFoundException::class);
        $role = factory(Role::class)->create();
        $permission = factory(Permission::class)->create();
        $role->permissions()->attach(Permission::all());
        $role->permissions()->findOrFail(10);
    }
    
    /** @test */
    public function test_findOrNew_method()
    {
        $role = factory(Role::class)->create();
        $permission = factory(Permission::class)->create();
        $role->permissions()->attach(Permission::all());

        $this->assertEquals($permission->uid, $role->permissions()->findOrNew($permission->uid)->uid);
        $this->assertNull($role->permissions()->findOrNew('asd')->uid);
        $this->assertInstanceOf(Permission::class, $role->permissions()->findOrNew('asd'));
    }

    /** @test */
    public function test_firstOrCreate_method()
    {
        $role = factory(Role::class)->create();
        $permission = factory(Permission::class)->create();
        $role->permissions()->attach(Permission::all());
        $this->assertEquals($permission->uid, $role->permissions()->firstOrCreate(['name' => $permission->name])->uid);
        $new = $role->permissions()->firstOrCreate(['name' => 'wavez']);
        $this->assertEquals('wavez', $new->name);
        $this->assertNotNull($new->uid);
    }

    /** @test */
    public function test_updateOrCreate_method()
    {
        $role = factory(Role::class)->create();
        $permission = factory(Permission::class)->create();
        $role->permissions()->attach(Permission::all());
        $role->permissions()->updateOrCreate([$permission->getTable() . '.uid' => $permission->uid], ['name' => 'wavez']);
        $this->assertEquals('wavez', $permission->fresh()->name);
        $role->permissions()->updateOrCreate([$permission->getTable() . '.uid' => 'asd'], ['name' => 'dives']);
        $this->assertNotNull($role->permissions()->whereName('dives')->first());
    }

}
