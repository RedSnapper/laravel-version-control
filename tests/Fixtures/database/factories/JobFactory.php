<?php

use Faker\Generator as Faker;
use Redsnapper\LaravelVersionControl\Tests\Fixtures\Models\Job;

$factory->define(Job::class, function (Faker $faker) {
    return [
      'title' => $this->faker->word,
    ];
});
