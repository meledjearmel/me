<?php

namespace Database\Factories;

use App\Models\PostSeries;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostSeries>
 */
class PostSeriesFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->words(3, true));

        return [
            'slug' => str($name)->slug()->toString(),
            'name' => ['fr' => $name, 'en' => $name],
        ];
    }
}
