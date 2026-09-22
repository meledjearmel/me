<?php

namespace Database\Factories;

use App\Models\JobProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobProfile>
 */
class JobProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $label = fake()->unique()->jobTitle();

        return [
            'key' => str($label)->slug()->toString(),
            'label' => [
                'fr' => $label,
                'en' => $label,
            ],
            'description' => [
                'fr' => fake()->sentence(),
                'en' => fake()->sentence(),
            ],
            'cv_description' => [
                'fr' => fake()->paragraphs(2, true),
                'en' => fake()->paragraphs(2, true),
            ],
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
