<?php

namespace Database\Factories;

use App\Enums\EngagementStatus;
use App\Enums\EngagementType;
use App\Models\Engagement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Engagement>
 */
class EngagementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => EngagementType::Freelance,
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'company' => fake()->company(),
            'subject' => fake()->sentence(3),
            'message' => fake()->paragraph(),
            'locale' => 'fr',
            'status' => EngagementStatus::New,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }

    public function hiring(): static
    {
        return $this->state(fn (): array => [
            'type' => EngagementType::Hiring,
            'contract' => 'cdi',
        ]);
    }
}
