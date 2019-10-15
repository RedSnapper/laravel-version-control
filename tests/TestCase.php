<?php

namespace Redsnapper\LaravelVersionControl\Tests;

use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Redsnapper\LaravelVersionControl\Models\Version;
use Redsnapper\LaravelVersionControl\Tests\Fixtures\Models\User;
use Redsnapper\LaravelVersionControl\VersionControlServiceProvider;

class TestCase extends OrchestraTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(realpath(__DIR__.'/Fixtures/database/migrations'));
        $this->withFactories(realpath(__DIR__.'/Fixtures/database/factories'));

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
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $app['config']->set('version-control.user', User::class);
        $app['config']->set('version-control.default_modifying_user',
            ['email' => 'laravelversioncontrol@redsnapper.net']);
        $app['config']->set('version-control.version_model', Version::class);
    }
}
