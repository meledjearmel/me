<?php

namespace Database\Factories;

use App\Models\Experience;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Experience>
 */
class ExperienceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $role = fake()->jobTitle();
        $startDate = fake()->dateTimeBetween('-8 years', '-1 year');

        return [
            'company' => fake()->company(),
            'role' => [
                'fr' => $role,
                'en' => $role,
            ],
            'location' => fake()->city(),
            'start_date' => $startDate,
            'end_date' => fake()->boolean(70) ? fake()->dateTimeBetween($startDate, 'now') : null,
            'description' => [
                'fr' => fake()->paragraph(),
                'en' => fake()->paragraph(),
            ],
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
