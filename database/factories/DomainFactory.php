<?php

namespace Database\Factories;

use App\Models\Domain;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Domain>
 */
class DomainFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $label = fake()->unique()->words(2, true);

        return [
            'key' => str(fake()->unique()->word())->slug()->toString(),
            'label' => [
                'fr' => ucfirst($label),
                'en' => ucfirst($label),
            ],
            'color' => fake()->hexColor(),
            'icon' => fake()->word(),
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
