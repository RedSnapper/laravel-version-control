<?php

use Illuminate\Foundation\Auth\User;
use Redsnapper\LaravelVersionControl\Models\Version;

return [
    'user' => User::class,
    'default_modifying_user' => ['email' => 'laravelversioncontrol@redsnapper.net'],
    'version_model' => Version::class
];
