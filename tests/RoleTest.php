<?php

namespace Redsnapper\LaravelVersionControl\Tests;

use Redsnapper\LaravelVersionControl\Tests\Fixtures\Models\Role;

class RoleTest extends BaseTest implements BaseModelTest
{
    private function params($overrides = [])
    {
        return array_merge([
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
        ], $overrides);
    }

    public function setupModel(array $overrides = [], ?string $key = null)
    {
        if(is_null($key)) {
            $this->model = Role::createNew($this->params());
        } else {
            $this->model = Role::saveChanges($this->params(), $key);
        }
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
    public function cannot_be_saved_outside_of_version_control()
    {
        $this->attempt_to_save_outside_of_version_control();
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
