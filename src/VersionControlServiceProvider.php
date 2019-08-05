<?php

namespace Redsnapper\LaravelVersionControl;

use Illuminate\Support\ServiceProvider;

class VersionControlServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([__DIR__ . '/config' => config_path()], 'redsnapper-laravel-version-control');
        //TODO Review whether this is correct
        $this->publishes([__DIR__ => app_path()], 'redsnapper-laravel-version-control-src');
    }
}
