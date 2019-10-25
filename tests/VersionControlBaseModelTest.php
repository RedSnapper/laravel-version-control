<?php

namespace Redsnapper\LaravelVersionControl\Tests;

use Illuminate\Support\Carbon;
use Redsnapper\LaravelVersionControl\Exceptions\ReadOnlyException;
use Redsnapper\LaravelVersionControl\Models\Version;
use Redsnapper\LaravelVersionControl\Tests\Fixtures\Models\User;

class VersionControlBaseModelTest extends TestCase
{
    protected $model;

    /** @test */
    public function can_create_new_record()
    {
        $user = factory(User::class)->create([
          'email' => 'john@example.com',
        ]);


        $this->assertEquals('john@example.com',$user->email);
        $this->assertTrue($user->vc_active);
        $this->assertCount(1,$user->versions);

        $version = $user->versions->first();
        $this->assertEquals('john@example.com',$version->email);
        $this->assertTrue($version->isActive());
        $this->assertFalse($version->isDeleted());

        $this->assertEquals($version->getKey(),$user->currentVersion->getKey());

    }

    /** @test */
    public function can_create_new_version_of_existing_record()
    {
        $user = factory(User::class)->create([
          'email' => 'john@example.com',
        ]);
        $user->email = "jane@example.com";
        $user->save();

        $this->assertCount(2,$user->versions);

        tap($user->currentVersion,function(Version $version) use($user){
            $this->assertEquals('jane@example.com',$version->email);
            $this->assertTrue($version->isActive());
            $this->assertEquals($user->password,$version->password);
            $this->assertEquals($version->getKey(),$user->currentVersion->getKey());
        });

    }

    /** @test */
    public function saving_a_model_which_hasnt_changed_doesnt_create_a_new_version()
    {
        $user = factory(User::class)->create([
          'email' => 'john@example.com',
        ]);
        $user->save();

        $this->assertCount(1,$user->versions);
    }

    /** @test */
    public function can_be_deleted()
    {
        $user = factory(User::class)->create();
        $user->delete();

        $this->assertCount(2,$user->versions);
        $this->assertFalse($user->exists);
        $this->assertTrue($user->trashed());

        tap($user->currentVersion,function(Version $version){
            $this->assertTrue($version->isDeleted());
        });

        $this->assertNull(User::find($user->getKey()));

    }

    /** @test */
    public function can_retrieve_trashed_model()
    {
        $userA = factory(User::class)->create();
        $userA->delete();
        $userB = factory(User::class)->create();
        $this->assertCount(2,User::withTrashed()->get());
        $this->assertCount(1,User::onlyTrashed()->get());

        $this->assertTrue($userA->is(User::onlyTrashed()->first()));
        $this->assertCount(1,User::all());
    }

    /** @test */
    public function versions_are_returned_latest_first()
    {
        $user = factory(User::class)->create(['email'=>'version1@tests.com']);
        Carbon::setTestNow(now()->addMinute());
        $user->email = "version2@tests.com";
        $user->save();

        $this->assertEquals('version2@tests.com', $user->versions()->first()->email);
    }

    /** @test */
    public function version_latest_scope_can_be_removed()
    {
        $user = factory(User::class)->create(['email'=>'version1@tests.com']);
        Carbon::setTestNow(now()->addMinute());
        $user->email = "version2@tests.com";
        $user->save();

        $this->assertEquals('version1@tests.com',
          $user->versions()->withoutGlobalScope('mostRecent')->orderBy('created_at','asc')->first()->email);
    }

    /** @test */
    public function can_be_restored_to_old_version()
    {
        $user = factory(User::class)->create(['email'=>'version1@tests.com']);
        $user->email = "version2@tests.com";
        $user->save();

        $version = $user->versions()->oldest()->first();
        $version->restore($user);

        tap($user->fresh(),function(User $user) use($version){
            $this->assertEquals("version1@tests.com", $user->email);
            $this->assertCount(3,$user->versions);
            $this->assertTrue($version->is($user->currentVersion->parent));
        });


        $user->email = "version4@redsnapper.net";
        $user->save();

        $user->restore($version);

        tap($user->fresh(),function(User $user) use($version){
            $this->assertEquals("version1@tests.com", $user->email);
            $this->assertCount(5,$user->versions);
            $this->assertTrue($version->is($user->currentVersion->parent));
        });

        $user->email = "version6@redsnapper.net";
        $user->save();

        $user->restore($version->getKey());

        tap($user->fresh(),function(User $user) use($version){
            $this->assertEquals("version1@tests.com", $user->email);
            $this->assertCount(7,$user->versions);
            $this->assertTrue($version->is($user->currentVersion->parent));
        });

    }

    /** @test */
    public function dates_on_restore_are_correct()
    {
        $oldDate = Carbon::create(2019, 1, 31);
        Carbon::setTestNow($oldDate);

        $user = factory(User::class)->create(['email'=>'version1@tests.com']);
        $user->email = "version2@tests.com";
        $user->save();

        $version = $user->versions()->oldest()->first();

        $newDate = Carbon::create(2020, 1, 31);
        Carbon::setTestNow($newDate);
        $version->restore($user);

        $this->assertEquals($newDate,$user->currentVersion->created_at);
        $this->assertEquals($oldDate,$user->created_at);
        $this->assertEquals($newDate,$user->updated_at);
    }

    /** @test */
    public function a_version_always_has_a_default_owner()
    {
        $userA = factory(User::class)->create();

        $this->assertNotNull($userA->currentVersion->modifyingUser);
        $this->assertEquals(config('version-control.default_modifying_user')['email'], $userA->currentVersion->modifyingUser->email);
    }

    /** @test */
    public function a_version_may_have_an_owner()
    {
        $userA = factory(User::class)->create();
        $this->actingAs($userA);

        $userB = factory(User::class)->create();

        $this->assertTrue($userA->is($userB->currentVersion->modifyingUser));
    }


    /** @test */
    public function can_validate_its_data()
    {
        $user = factory(User::class)->create();
        $this->assertTrue($user->validateData());

        $user->email ="foo";

        $this->assertFalse($user->validateData());
    }

    /** @test */
    public function can_validate_its_own_version()
    {
        $user = factory(User::class)->create();
        $this->assertTrue($user->validateVersion());
    }

    /** @test */
    public function cannot_delete_model_using_destroy()
    {
        $this->expectException(ReadOnlyException::class);
        $user = factory(User::class)->create();
        $user->destroy($user->uid);
    }

    /** @test */
    public function cannot_delete_model_using_truncate()
    {
        $this->expectException(ReadOnlyException::class);
        $user = factory(User::class)->create();
        $user->truncate();
    }

    /** @test */
    public function cannot_delete_version_using_delete()
    {
        $this->expectException(ReadOnlyException::class);
        $user = factory(User::class)->create();
        $user->currentVersion->delete();
    }

    /** @test */
    public function cannot_delete_version_using_destroy()
    {
        $this->expectException(ReadOnlyException::class);
        $user = factory(User::class)->create();
        $version = $user->currentVersion;
        $version->destroy($version->uid);
    }

    /** @test */
    public function cannot_delete_version_using_truncate()
    {
        $this->expectException(ReadOnlyException::class);
        $user = factory(User::class)->create();
        $version = $user->currentVersion;
        $version->truncate();
    }

    /** @test */
    public function can_not_insert_on_a_model()
    {
        $this->expectException(ReadOnlyException::class);
        $user = new User();
        $user->insert(['email'=>'foo']);
    }

    /** @test */
    public function can_touch_a_model()
    {
        $oldDate = Carbon::create(2018, 1, 31);
        Carbon::setTestNow($oldDate);
        $user = factory(User::class)->create();

        $newDate = Carbon::create(2019, 1, 31);
        Carbon::setTestNow($newDate);
        $user->touch();

        $this->assertEquals($newDate,$user->updated_at);
        $this->assertEquals($oldDate,$user->created_at);
        $this->assertCount(2,$user->versions);
        $this->assertEquals($newDate,$user->currentVersion->created_at);
    }


    /** @test */
    public function can_update_an_unguarded_model()
    {
        Version::unguard();

        $user = factory(User::class)->create();
        $user->email = "john@example.com";
        $user->save();

        $this->assertEquals('john@example.com',$user->currentVersion->email);

    }

    /** @test */
    public function vc_active_is_a_boolean()
    {
        $user = factory(User::class)->create();

        $this->assertTrue($user->fresh()->vc_active);
    }

}
