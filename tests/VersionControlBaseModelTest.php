<?php

namespace Redsnapper\LaravelVersionControl\Tests;

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
    }

    /** @test */
    public function can_create_new_record()
    {
        $this->setupModel();
        $this->assertInstanceOf(User::class, $this->model);
    }

    /** @test */
    public function can_create_new_version_of_existing_record()
    {
        $this->setupModel();
        $this->setupModel([], $this->model->uid);
        $this->assertEquals(2, $this->model->versions()->count());
    }

    /** @test */
    public function can_validate_its_data()
    {
        $this->setupModel();
        $this->assertTrue($this->model->validateData());
    }

    /** @test */
    public function can_validate_its_own_version()
    {
        $this->setupModel();
        $this->assertTrue($this->model->validateVersion());
    }

    /** @test */
    public function can_be_restored_to_old_version()
    {
        $this->setupModel(["email" => "version1@tests.com"]);
        $this->assertEquals(1, $this->model->vc_version);

        $this->setupModel(["email" => "version2@tests.com"], $this->model->uid);
        $this->assertEquals(2, $this->model->vc_version);

        $this->model->restore(1);

        $this->assertEquals("version1@tests.com", $this->model->email);
        $this->assertEquals(3, $this->model->vc_version);
    }

    /** @test */
    public function can_be_deleted()
    {
        $this->setupModel();
        $this->assertEquals(1, $this->model->vc_version);
        $this->assertEquals(1, $this->model->vc_active);

        $this->model->delete();
        $this->model->fresh();

        $this->assertEquals(2, $this->model->vc_version);
        $this->assertEquals(0, $this->model->vc_active);

        // Cant get this user now as they have been deleted...
        $model = User::find($this->model->uid);
        $this->assertNull($model);
    }
}
