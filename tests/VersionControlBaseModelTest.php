<?php

namespace Redsnapper\LaravelVersionControl\Tests;

use Redsnapper\LaravelVersionControl\Models\Version;
use Redsnapper\LaravelVersionControl\Tests\Fixtures\Models\User;

class VersionControlBaseModelTest extends Base
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
    public function can_be_deleted()
    {
        $user = factory(User::class)->create();
        $user->delete();

        $this->assertCount(2,$user->versions);
        $this->assertFalse($user->exists);

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
    public function a_version_may_have_an_owner()
    {
        $userA = factory(User::class)->create();

        $this->assertNull($userA->currentVersion->modifyingUser);

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

}
