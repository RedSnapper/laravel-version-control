<?php

namespace Redsnapper\LaravelVersionControl\Tests;

use Redsnapper\LaravelVersionControl\Exceptions\ReadOnlyException;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Redsnapper\LaravelVersionControl\Tests\Fixtures\Models\Job;
use Redsnapper\LaravelVersionControl\Tests\Fixtures\Models\PermissionRole;
use Redsnapper\LaravelVersionControl\Tests\Fixtures\Models\Post;
use Redsnapper\LaravelVersionControl\Tests\Fixtures\Models\User;
use Redsnapper\LaravelVersionControl\Tests\Fixtures\Models\Role;
use Redsnapper\LaravelVersionControl\Tests\Fixtures\Models\Permission;

class Base extends TestCase
{
    use RefreshDatabase, WithFaker;


    protected function createPermission(array $overrides = []): Permission
    {
        $permission = (new Permission())->fill(array_merge([
            'vc_active' => 1,
            'vc_modifier_uid' => null,
            'name' => $this->faker->jobTitle,
        ], $overrides));

        $permission->save();
        return $permission;
    }

    protected function createJob(array $overrides = []): Job
    {
        $job = (new Job())->fill(array_merge([
            'vc_active' => 1,
            'vc_modifier_uid' => null,
            'user_uid' => ($this->createUser())->uid,
            'title' => $this->faker->word,
        ], $overrides));

        $job->save();
        return $job;
    }
}
