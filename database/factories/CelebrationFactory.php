<?php

namespace Database\Factories;

use App\Models\Celebration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Celebration>
 */
class CelebrationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'message' => ['fr' => fake()->sentence(), 'en' => fake()->sentence()],
            'button_label' => ['fr' => 'Féliciter', 'en' => 'Congratulate'],
            'congratulated_for' => 'votre '.fake()->words(3, true),
            'is_active' => true,
            'starts_at' => null,
            'ends_at' => null,
            'weight' => 1,
            'chance_percent' => 33,
            'delay_seconds' => 9,
            'display_seconds' => 15,
            'snooze_days' => 7,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
