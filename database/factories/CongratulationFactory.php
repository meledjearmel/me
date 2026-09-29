<?php

namespace Database\Factories;

use App\Enums\CongratulationSource;
use App\Models\Congratulation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Congratulation>
 */
class CongratulationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source' => CongratulationSource::About,
            'celebration_id' => null,
            'reason' => Congratulation::ABOUT_REASON,
            'count' => fake()->numberBetween(1, 5),
            'locale' => 'fr',
        ];
    }
}
