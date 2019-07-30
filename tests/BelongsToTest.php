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
        $role = $this->createRole();
        $user = $this->createUser(['role_uid' => $role->uid]);

        $this->assertEquals($user->role_uid, $role->uid);
        $this->assertInstanceOf(BelongsTo::class, $user->role());
        $this->assertInstanceOf(Role::class, $user->role);
    }

    /** @test */
    public function only_an_active_relation_can_be_returned()
    {
        $role = $this->createRole();
        $user = $this->createUser(['role_uid' => $role->uid]);

        $this->assertEquals($user->role_uid, $role->uid);
        $this->assertInstanceOf(BelongsTo::class, $user->role());
        $this->assertInstanceOf(Role::class, $user->role);

        $role->delete();

        $user = User::find($user->uid);

        $this->assertNotInstanceOf(Role::class, $user->role);
    }
}
