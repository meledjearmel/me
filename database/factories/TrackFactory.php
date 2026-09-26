<?php

namespace Database\Factories;

use App\Models\MusicGenre;
use App\Models\Track;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Track>
 */
class TrackFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'music_genre_id' => MusicGenre::factory(),
            'title' => fake()->words(3, true),
            'artist' => fake()->name(),
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
