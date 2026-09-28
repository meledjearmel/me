<?php

namespace Database\Factories;

use App\Models\Technology;
use App\Models\TechnologyCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Technology>
 */
class TechnologyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'category_id' => TechnologyCategory::factory(),
            'icon' => fake()->word(),
            'description' => [
                'fr' => fake()->sentence(),
                'en' => fake()->sentence(),
            ],
        ];
    }
}
