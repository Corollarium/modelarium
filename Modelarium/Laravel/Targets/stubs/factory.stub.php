<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */
use Faker\Generator as Faker;

$factory->define(App\DummyName::class, function (Faker $faker) {
    return (new App\Formularium\DummyName())->getRandom();
});
