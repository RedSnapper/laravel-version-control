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
    public function can_validate_its_own_version()
    {
        $this->validate_version();
    }
}
