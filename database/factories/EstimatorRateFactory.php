<?php

namespace Database\Factories;

use App\Models\EstimatorRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstimatorRate>
 */
class EstimatorRateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ['fr' => 'Développement avancé', 'en' => 'Advanced development'],
            'hourly_rates' => ['CHF' => 150, 'EUR' => 140],
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
