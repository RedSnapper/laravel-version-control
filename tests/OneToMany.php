<?php

namespace Redsnapper\LaravelVersionControl\Tests;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Redsnapper\LaravelVersionControl\Tests\Fixtures\Models\Role;
use Redsnapper\LaravelVersionControl\Tests\Fixtures\Models\User;

class OneToMany extends Base
{
    /** @test */
    public function a_vc_model_can_belong_to_another()
    {
        $role = factory(Role::class)->create();
        $userA = factory(User::class)->create(['role_uid' => $role->uid]);
        $userB = factory(User::class)->create(['role_uid' => $role->uid]);

        $this->assertTrue($userA->role->is($role));
        $this->assertCount(2,$role->users);

        $userB->delete();

        $this->assertCount(1,$role->fresh()->users);

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
