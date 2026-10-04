<?php

namespace Database\Factories;

use App\Models\Subscriber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscriber>
 */
class SubscriberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'locale' => 'fr',
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (): array => ['confirmed_at' => now()]);
    }

    public function unsubscribed(): static
    {
        return $this->state(fn (): array => ['confirmed_at' => now()->subMonth(), 'unsubscribed_at' => now()]);
    }
}
