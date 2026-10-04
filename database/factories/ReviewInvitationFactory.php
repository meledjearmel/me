<?php

namespace Database\Factories;

use App\Models\ReviewInvitation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReviewInvitation>
 */
class ReviewInvitationFactory extends Factory
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
            'email' => fake()->safeEmail(),
            'locale' => 'fr',
        ];
    }

    public function used(): static
    {
        return $this->state(fn (): array => ['used_at' => now()]);
    }

    public function expired(): static
    {
        return $this->state(fn (): array => ['expires_at' => now()->subDay()]);
    }
}
