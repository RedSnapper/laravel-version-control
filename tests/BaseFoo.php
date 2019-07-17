<?php

namespace Redsnapper\LaravelVersionControl\Tests;

use Redsnapper\LaravelVersionControl\Exceptions\ReadOnlyException;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Redsnapper\LaravelVersionControl\Tests\Fixtures\Models\User;
use Redsnapper\LaravelVersionControl\Tests\Fixtures\Models\Role;
use Redsnapper\LaravelVersionControl\Tests\Fixtures\Models\Permission;

class BaseTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $model;

    /**
     * @param $object
     */
    public function create_new_record($object)
    {
        $this->setupModel();
        $this->assertIsObject($this->model, $object);
    }

    public function create_new_version_of_existing_record()
    {
        $this->setupModel();
        $this->setupModel([], $this->model->unique_key);
        $this->assertEquals(2, $this->model->versions()->count());
    }

    public function validate_version()
    {
        $this->setupModel();
        $this->assertTrue($this->model->validateVersion());
    }

    public function validate_data()
    {
        $this->setupModel();
        $this->assertTrue($this->model->validateData());
    }

    public function attempt_to_save_outside_of_version_control()
    {
        $this->setupModel();
        $this->expectException(ReadOnlyException::class);
        $this->model->save();
    }

    protected function createUser()
    {
        return User::createNew([
            'vc_active' => 1,
            'vc_modifier_unique_key' => null,
            'username' => $this->faker->firstName,
            'email' => $this->faker->unique()->safeEmail,
            'emailp' => $this->faker->unique()->safeEmail,
            'password' => 'secret',
            'active' => 'on'
        ]);
    }

    protected function createPermission(array $overrides = [])
    {
        return Permission::createNew(array_merge([
            'vc_active' => 1,
            'vc_modifier_unique_key' => null,
            'name' => $this->faker->jobTitle,
            'active' => 'on'
        ], $overrides));
    }

    protected function createRole(array $overrides = [])
    {
        return Role::createNew(array_merge([
            'vc_active' => 1,
            'vc_modifier_unique_key' => null,
            'category_unique_key' => $this->faker->word,
            'name' => $this->faker->jobTitle,
            'hidden' => rand(0,1),
            'level' => rand(0,30),
            'view' => rand(0,800),
            'comment' => $this->faker->sentence,
            'alphasort' => array_rand(['m50','z50','d50','j10','c40','b30']),
            'active' => 'on'
        ], $overrides));
    }
}
