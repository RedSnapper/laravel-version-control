<?php

namespace Redsnapper\LaravelVersionControl\Tests;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Redsnapper\LaravelVersionControl\Tests\Fixtures\Models\Job;
use Redsnapper\LaravelVersionControl\Tests\Fixtures\Models\User;

class OneToOneTest extends Base
{
    /** @test */
    public function a_vc_model_can_have_a_has_one_relation()
    {
        $user = factory(User::class)->create();
        $job = factory(Job::class)->create(['user_uid' => $user->uid]);

        $this->assertTrue($user->job->is($job));
        $this->assertTrue($job->user->is($user));

    }

    /** @test */
    public function only_active_records_are_returned_by_has_one()
    {
        $user = factory(User::class)->create();
        $job = factory(Job::class)->create(['user_uid' => $user->uid]);

        $this->assertTrue($user->job->is($job));

        $user->job->delete();

        $this->assertNull($user->fresh()->job);
        $this->assertTrue($user->job()->withTrashed()->get()->first()->is($job));


    }
}
