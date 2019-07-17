<?php

namespace Redsnapper\LaravelVersionControl\Tests;

use Redsnapper\LaravelVersionControl\Tests\Fixtures\Models\Role;

class RoleTest extends Base implements BaseModelTest
{
    private function params($overrides = [])
    {
        return array_merge([
            'vc_active' => 1,
            'vc_modifier_uid' => null,
            'category_uid' => $this->faker->word,
            'name' => $this->faker->jobTitle,
            'hidden' => rand(0,1),
            'level' => rand(0,30),
            'view' => rand(0,800),
            'comment' => $this->faker->sentence,
            'alphasort' => array_rand(['m50','z50','d50','j10','c40','b30']),
            'active' => 'on'
        ], $overrides);
    }

    public function setupModel(array $overrides = [], ?string $key = null)
    {
        $params = $this->params($overrides);

        if(is_null($key)) {
            $params = array_merge($params, ['uid' => $key]);
            $this->model = (new Role())->fill($params);
        } else {
            $this->model = Role::find($key);
        }

        $this->model->save();
    }

    /** @test */
    public function can_create_new_record()
    {
        $this->create_new_record(Role::class);
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
    public function roles_can_have_many_permissions()
    {
        $permissionA = $this->createPermission(["name" => "can-see-the-ground"]);
        $permissionB = $this->createPermission(["name" => "can-see-the-sky"]);

        $this->setupModel(['name' => 'Role for bad necks']);
        $this->model->givePermissionTo($permissionA);

        $this->assertTrue($this->model->hasPermission('can-see-the-ground'));
        $this->assertFalse($this->model->hasPermission('can-see-the-sky'));

        // Permissions update when adding new permissions to a role
        $this->model->givePermissionTo($permissionB);
        $this->assertTrue($this->model->hasPermission('can-see-the-sky'));
    }

    /** @test */
    public function roles_can_have_permissions_removed()
    {
        $permissionA = $this->createPermission(["name" => "can-see-the-ground"]);
        $permissionB = $this->createPermission(["name" => "can-see-the-sky"]);

        $this->setupModel(['name' => 'Role for bad necks']);
        $this->model->givePermissionTo($permissionA);

        $this->assertTrue($this->model->hasPermission('can-see-the-ground'));
        $this->assertFalse($this->model->hasPermission('can-see-the-sky'));

        // Permissions update when adding new permissions to a role
        $this->model->removePermission($permissionA);
        $this->assertFalse($this->model->hasPermission('can-see-the-ground'));
    }
}
