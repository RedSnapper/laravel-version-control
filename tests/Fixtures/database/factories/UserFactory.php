<?php

use Faker\Generator as Faker;
use Redsnapper\LaravelVersionControl\Tests\Fixtures\Models\User;

$factory->define(User::class, function (Faker $faker) {
    return [
      'email'    => $this->faker->unique()->safeEmail,
      'password' => 'secret'
    ];
});
