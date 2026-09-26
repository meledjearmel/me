<?php

namespace Database\Factories;

use App\Models\MusicGenre;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MusicGenre>
 */
class MusicGenreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $label = ucfirst(fake()->unique()->word());

        return [
            'key' => str($label)->slug()->toString(),
            'label' => ['fr' => $label, 'en' => $label],
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
