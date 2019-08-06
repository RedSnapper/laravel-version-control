<?php

namespace Redsnapper\LaravelVersionControl\Tests;

use Redsnapper\LaravelVersionControl\Models\Versioned;
use Redsnapper\LaravelVersionControl\Tests\Fixtures\Models\User;

class VersionControlBaseModelTest extends Base
{
    protected $model;

    private function params($overrides = [])
    {
        return array_merge([
            'vc_active' => 1,
            'vc_modifier_uid' => null,
            'role_uid' => ($this->createRole())->uid,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'secret',
        ], $overrides);
    }

    public function setupModel(array $overrides = [], ?string $key = null)
    {
        $params = $this->params($overrides);

        if(is_null($key)) {
            $params = array_merge($params, ['uid' => $key]);
            $this->model = (new User())->fill($params);
        } else {
            $this->model = User::find($key);
        }

        $this->model->save();

        return $this->model;
    }

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
            $this->assertEquals(2,$version->vc_version);
            $this->assertTrue($version->isDeleted());
        });

        $this->assertNull(User::find($user->getKey()));

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

    /** @test */
    //public function can_be_restored_to_old_version()
    //{
    //    $this->setupModel(["email" => "version1@tests.com"]);
    //    $this->assertEquals(1, $this->model->vc_version);
    //
    //    $this->setupModel(["email" => "version2@tests.com"], $this->model->uid);
    //    $this->assertEquals(2, $this->model->vc_version);
    //
    //    $this->model->restore(1);
    //
    //    $this->assertEquals("version1@tests.com", $this->model->email);
    //    $this->assertEquals(3, $this->model->vc_version);
    //}
}
