<?php

namespace Database\Factories;

use App\Models\Education;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Education>
 */
class EducationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $degree = fake()->word().' '.fake()->word();
        $field = fake()->word().' '.fake()->word();
        $startDate = fake()->dateTimeBetween('-10 years', '-3 years');

        return [
            'institution' => fake()->company(),
            'degree' => [
                'fr' => ucfirst($degree),
                'en' => ucfirst($degree),
            ],
            'field' => [
                'fr' => ucfirst($field),
                'en' => ucfirst($field),
            ],
            'start_date' => $startDate,
            'end_date' => fake()->dateTimeBetween($startDate, '-1 year'),
            'description' => [
                'fr' => fake()->sentence(),
                'en' => fake()->sentence(),
            ],
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
