<?php

namespace Redsnapper\LaravelVersionControl\Tests;

use Redsnapper\LaravelVersionControl\Models\Versioned;
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

        tap($user->currentVersion,function(Versioned $version) use($user){
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

        tap($user->currentVersion,function(Versioned $version){
            $this->assertTrue($version->isDeleted());
        });

        $this->assertNull(User::find($user->getKey()));

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

    }


    ///** @test */
    //public function can_validate_its_data()
    //{
    //    $this->setupModel();
    //    $this->assertTrue($this->model->validateData());
    //}
    //
    ///** @test */
    //public function can_validate_its_own_version()
    //{
    //    $this->setupModel();
    //    $this->assertTrue($this->model->validateVersion());
    //}
    
}
