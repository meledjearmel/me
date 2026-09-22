<?php

namespace Database\Factories;

use App\Models\Experience;
use App\Models\ExperienceHighlight;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExperienceHighlight>
 */
class ExperienceHighlightFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'experience_id' => Experience::factory(),
            'text' => [
                'fr' => fake()->sentence(),
                'en' => fake()->sentence(),
            ],
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
