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

}
