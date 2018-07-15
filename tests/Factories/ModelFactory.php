<?php

namespace BeyondCode\QueryDetector\Tests\Seeder;

use BeyondCode\QueryDetector\Tests\Models\Post;
use BeyondCode\QueryDetector\Tests\Models\Author;
use BeyondCode\QueryDetector\Tests\Models\Profile;
use BeyondCode\QueryDetector\Tests\Models\Comment;

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(Post::class, function ($faker) {
    return [
        'title' => $faker->sentence,
        'author_id' => function () {
            return factory(Author::class)->create()->id;
        },
        'body' => $faker->paragraphs(rand(3,10), true),
    ];
});

$factory->define(Author::class, function ($faker) {
    return [
        'name' => $faker->name,
        'bio' => $faker->paragraph,
    ];
});

$factory->define(Profile::class, function ($faker) {
    return [
        'birthday' => $faker->dateTimeBetween('-100 years', '-18 years'),
        'author_id' => function () {
            return factory(Author::class)->create()->id;
        },
        'city' => $faker->city,
        'state' => $faker->state,
        'website' => $faker->domainName,
    ];
});

$factory->define(Comment::class, function ($faker) {
    return [
        'body' => $faker->paragraph,
    ];
});