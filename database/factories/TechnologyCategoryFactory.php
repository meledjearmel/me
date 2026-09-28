<?php

namespace Database\Factories;

use App\Models\TechnologyCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TechnologyCategory>
 */
class TechnologyCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $label = fake()->unique()->word();

        return [
            'key' => str($label)->slug()->toString(),
            'label' => [
                'fr' => ucfirst($label),
                'en' => ucfirst($label),
            ],
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
