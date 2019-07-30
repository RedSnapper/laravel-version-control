<?php

namespace Redsnapper\LaravelVersionControl\Tests;

use Illuminate\Foundation\Application;
use Redsnapper\LaravelVersionControl\VersionControlServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Note: this also flushes the cache from within the migration
        $this->setUpDatabase($this->app);
    }

    /**
     * @param  Application  $app
     * @return array
     *
     */
    protected function getPackageProviders($app)
    {
        return [VersionControlServiceProvider::class];
    }

    /**
     * Set up the environment.
     *
     * @param Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }
    /**
     * Set up the database.
     *
     * @param Application $app
     */
    protected function setUpDatabase($app)
    {
        include_once __DIR__.'/Fixtures/database/migrations/create_users_table.php';
        include_once __DIR__.'/Fixtures/database/migrations/create_roles_table.php';
        include_once __DIR__.'/Fixtures/database/migrations/create_permissions_table.php';
        include_once __DIR__.'/Fixtures/database/migrations/create_permission_role_table.php';
        include_once __DIR__.'/Fixtures/database/migrations/create_jobs_table.php';
        include_once __DIR__.'/Fixtures/database/migrations/create_posts_table.php';

        (new CreateUsersTable())->up();
        (new CreateRolesTable())->up();
        (new CreatePermissionsTable())->up();
        (new CreatePermissionRoleTable())->up();
        (new CreateJobsTable())->up();
        (new CreatePostsTable())->up();
    }
}
