<?php

namespace Redsnapper\LaravelVersionControl\Tests;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Redsnapper\LaravelVersionControl\Tests\Fixtures\Models\Role;
use Redsnapper\LaravelVersionControl\Tests\Fixtures\Models\User;

class BelongsToTest extends Base
{
    /** @test */
    public function a_vc_model_can_belong_to_another()
    {
        $role = factory(Role::class)->create();
        $user = factory(User::class)->create(['role_uid' => $role->uid]);

        $this->assertTrue($user->role->is($role));
        $this->assertTrue($user->is($role->users->first()));

    }

    /** @test */
    public function only_an_active_relation_can_be_returned()
    {
        $role = factory(Role::class)->create();
        $user = factory(User::class)->create(['role_uid' => $role->uid]);
        $role->delete();

        $this->assertNull($user->role);
    }
}
