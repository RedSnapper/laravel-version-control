<?php

namespace Redsnapper\LaravelVersionControl\Tests\Fixtures;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Support\Collection;
use Redsnapper\LaravelVersionControl\Tests\Fixtures\Models\Permission;

class TestPermissionsRegistrar
{
    /** @var Gate */
    protected $gate;

    /**
     * @var Collection
     */
    protected $permissions;


    public function __construct(Gate $gate)
    {
        $this->gate = $gate;
    }

    /**
     * Register activities for auth
     *
     */
    public function register()
    {
        $this->gate->before(function (Authorizable $user, string $ability, $arguments) {

            // Must be passing a model with so we should ignore
            // Only want to check auth without arguments
            // Policies deal with authorization with arguments
            if (count($arguments) > 0) {
                return null;
            }

            return $user->hasPermissionTo($ability);
        });
    }

    /**
     * Returns all activities with the roles they belong to as well as all the
     * role activity instances
     *
     * @return Collection|static
     */
    public function getPermissions()
    {
        if (is_null($this->permissions)) {

            // We key by name so that when looking up activities we can find them
            // quicker
            $this->permissions = Permission::with(['roles'])
              ->get()
              ->keyBy('name');
        }

        return $this->permissions;
    }

    /**
     * Ensure next time we ask for permissions they are returned from the database
     */
    public function forgetCachedPermissions()
    {
        $this->permissions = null;
    }

}
