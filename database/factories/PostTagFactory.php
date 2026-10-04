<?php

namespace Database\Factories;

use App\Models\PostTag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostTag>
 */
class PostTagFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => ['fr' => ucfirst($name), 'en' => ucfirst($name)],
            'slug' => str($name)->slug()->toString(),
        ];
    }
}
