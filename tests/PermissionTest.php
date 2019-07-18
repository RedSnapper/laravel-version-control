<?php

namespace Redsnapper\LaravelVersionControl\Tests;

use Redsnapper\LaravelVersionControl\Tests\Fixtures\Models\Permission;

class PermissionTest extends Base implements BaseModelTest
{
    private function params($overrides = [])
    {
        return array_merge([
            'vc_active' => 1,
            'vc_modifier_uid' => null,
            'name' => $this->faker->jobTitle,
            'active' => 'on'
        ], $overrides);
    }

    public function setupModel(array $overrides = [], ?string $key = null)
    {
        $params = $this->params($overrides);

        if(is_null($key)) {
            $params = array_merge($params, ['uid' => $key]);
            $this->model = (new Permission())->fill($params);
        } else {
            $this->model = Permission::find($key);
        }

        $this->model->save();
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
    public function can_be_restored_to_old_version()
    {
        $this->setupModel(["name" => "Version 1"]);
        $this->assertEquals(1, $this->model->vc_version);

        $this->setupModel(["name" => "Version 2"], $this->model->uid);
        $this->assertEquals(2, $this->model->vc_version);

        $this->model->restore(1);

        $this->assertEquals("Version 1", $this->model->name);
        $this->assertEquals(3, $this->model->vc_version);
    }

    /** @test */
    public function can_be_deleted()
    {
        $this->delete_model();
    }

    /** @test */
    public function can_validate_its_own_version()
    {
        $this->validate_version();
    }
}
