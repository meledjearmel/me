<?php

namespace Database\Factories;

use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profile>
 */
class ProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'headline' => [
                'fr' => fake()->sentence(),
                'en' => fake()->sentence(),
            ],
            'bio_short' => [
                'fr' => fake()->paragraph(),
                'en' => fake()->paragraph(),
            ],
            'bio_full' => [
                'fr' => fake()->paragraphs(3, true),
                'en' => fake()->paragraphs(3, true),
            ],
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'location' => fake()->city(),
            'social_links' => [
                'github' => 'https://github.com/'.fake()->userName(),
                'linkedin' => 'https://linkedin.com/in/'.fake()->userName(),
            ],
        ];
    }
}
