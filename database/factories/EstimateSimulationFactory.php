<?php

namespace Database\Factories;

use App\Models\EstimateSimulation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstimateSimulation>
 */
class EstimateSimulationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'visitor_hash' => fake()->sha256(),
            'signature' => fake()->md5(),
            'project_type' => 'Site vitrine',
            'features' => [],
            'currency' => 'CHF',
            'price_min' => 4000,
            'price_max' => 6000,
        ];
    }
}
