<?php

namespace Redsnapper\LaravelVersionControl\Tests;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Redsnapper\LaravelVersionControl\Tests\Fixtures\Models\Job;
use Redsnapper\LaravelVersionControl\Tests\Fixtures\Models\User;

class HasOneTest extends Base
{
    /** @test */
    public function a_vc_model_can_have_a_has_one_relation()
    {
        $user = $this->createUser();
        $this->createJob(['user_uid' => $user->uid]);

        $this->assertInstanceOf(HasOne::class, $user->job());
        $this->assertInstanceOf(Job::class, $user->job);
    }

    /** @test */
    public function only_active_records_are_returned_by_has_one()
    {
        $user = $this->createUser();
        $uid = $user->uid;
        $this->createJob(['user_uid' => $user->uid]);

        $this->assertInstanceOf(HasOne::class, $user->job());
        $this->assertInstanceOf(Job::class, $user->job);

        $user->job->delete();

        // Now get the user fresh from the DB
        $user = User::find($uid);

        $this->assertInstanceOf(HasOne::class, $user->job());
        $this->assertNotInstanceOf(Job::class, $user->job);
    }
}
