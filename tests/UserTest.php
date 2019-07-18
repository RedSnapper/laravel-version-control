<?php

namespace Redsnapper\LaravelVersionControl\Tests;

use Redsnapper\LaravelVersionControl\Tests\Fixtures\Models\User;

class UserTest extends Base implements BaseModelTest
{
    private function params($overrides = [])
    {
        return array_merge([
            'vc_active' => 1,
            'vc_modifier_uid' => null,
            'username' => $this->faker->firstName,
            'email' => $this->faker->unique()->safeEmail,
            'emailp' => $this->faker->unique()->safeEmail,
            'password' => 'secret',
            'active' => 'on'
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
        $this->create_new_record(User::class);
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
    public function can_be_restored_to_old_version()
    {
        $this->setupModel(["username" => "Version 1"]);
        $this->assertEquals(1, $this->model->vc_version);

        $this->setupModel(["username" => "Version 2"], $this->model->uid);
        $this->assertEquals(2, $this->model->vc_version);

        $this->model->restore(1);

        $this->assertEquals("Version 1", $this->model->username);
        $this->assertEquals(3, $this->model->vc_version);
    }

    /** @test */
    public function can_be_deleted()
    {
        $this->delete_model();
    }

    /** @test */
    public function users_can_subscribe_to_roles()
    {
        $roleA = $this->createRole(["name" => "Role A"]);
        $roleB = $this->createRole(["name" => "Role B"]);

        $this->setupModel(['username' => 'personA']);
        $this->model->assignRole($roleA);

        $this->assertTrue($this->model->belongsToRole($roleA));
        $this->assertFalse($this->model->belongsToRole($roleB));

        $this->model->assignRole($roleB);
        $this->assertTrue($this->model->belongsToRole($roleA));
        $this->assertTrue($this->model->belongsToRole($roleB));
    }

    /** @test */
    public function users_can_be_unsubscribed_from_roles()
    {
        $roleA = $this->createRole(["name" => "Role A"]);
        $this->setupModel(['username' => 'personA']);

        $this->model->assignRole($roleA);
        $this->assertTrue($this->model->belongsToRole($roleA));

        $this->model->unAssignRole($roleA);
        $this->assertFalse($this->model->belongsToRole($roleA));
    }

    /** @test */
    public function users_get_permissions_from_roles()
    {
        $permissionA = $this->createPermission(["name" => "can-see-the-ground"]);
        $this->createPermission(["name" => "can-see-the-sky"]);

        $roleA = $this->createRole(["name" => "Role A"]);

        $roleA->givePermissionTo($permissionA);

        $this->setupModel(['username' => 'personA']);
        $this->model->assignRole($roleA);

        $this->assertTrue($this->model->hasPermissionTo('can-see-the-ground'));
        $this->assertFalse($this->model->hasPermissionTo('can-see-the-sky'));
    }
}
