<?php

namespace Database\Factories;

use App\Enums\TestimonialStatus;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Testimonial>
 */
class TestimonialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'author_name' => fake()->name(),
            'author_email' => fake()->unique()->safeEmail(),
            'author_role' => fake()->jobTitle(),
            'content' => [
                'fr' => fake()->paragraph(),
                'en' => fake()->paragraph(),
            ],
            'project_id' => null,
            'status' => TestimonialStatus::Pending,
            'submitted_at' => fake()->dateTimeBetween('-1 year'),
        ];
    }
}
