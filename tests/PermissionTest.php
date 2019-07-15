<?php

namespace Redsnapper\LaravelVersionControl\Tests;

use Redsnapper\LaravelVersionControl\Tests\Fixtures\Models\Permission;

class PermissionTest extends BaseTest implements BaseModelTest
{
    private function params($overrides = [])
    {
        return array_merge([
            'vc_active' => 1,
            'vc_modifier_unique_key' => null,
            'name' => $this->faker->jobTitle,
            'active' => 'on'
        ], $overrides);
    }

    public function setupModel(array $overrides = [], ?string $key = null)
    {
        if(is_null($key)) {
            $this->model = Permission::createNew($this->params());
        } else {
            $this->model = Permission::saveChanges($this->params(), $key);
        }
    }

    /** @test */
    public function can_create_new_record()
    {
        $this->create_new_record(Permission::class);
    }

    /** @test */
    public function can_create_new_version_of_existing_record()
    {
        $this->create_new_version_of_existing_record();
    }

    /** @test */
    public function can_validate_its_data()
    {
        $this->validate_data();
    }

    /** @test */
    public function can_validate_its_own_version()
    {
        $this->validate_version();
    }

    /** @test */
    public function cannot_be_saved_outside_of_version_control()
    {
        $this->attempt_to_save_outside_of_version_control();
    }
}
