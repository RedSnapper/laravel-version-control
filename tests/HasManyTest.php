<?php

namespace Redsnapper\LaravelVersionControl\Tests;

use Illuminate\Database\Eloquent\Relations\HasMany;

class HasManyTest extends Base
{
    /** @test */
    public function a_vc_model_can_have_many_others()
    {
        $user = $this->createUser();

        $this->createPost(['user_uid' => $user->uid]);
        $this->createPost(['user_uid' => $user->uid]);
        $this->createPost(['user_uid' => $user->uid]);

        $this->assertInstanceOf(HasMany::class, $user->posts());
        $this->assertEquals(3, $user->posts()->count());
    }

    /** @test */
    public function only_active_relations_are_returned_by_has_many()
    {
        $user = $this->createUser();
        $this->createPost(['user_uid' => $user->uid]);
        $this->createPost(['user_uid' => $user->uid]);
        $post = $this->createPost(['user_uid' => $user->uid]);

        $this->assertEquals(3, $user->posts()->count());

        $post->delete();

        $this->assertEquals(2, $user->posts()->count());
    }
}
