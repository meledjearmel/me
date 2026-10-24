<?php

namespace Database\Factories;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Proposal>
 */
class ProposalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'platform' => 'upwork',
            'locale' => 'fr',
            'job_title' => fake()->sentence(4),
            'client_name' => fake()->optional()->company(),
            'job_url' => fake()->optional()->url(),
            'job_posting' => fake()->paragraphs(2, true),
            'body' => fake()->paragraphs(3, true),
            'bid' => '40 $/h',
            'connects' => 16,
            'status' => ProposalStatus::Draft,
        ];
    }

    public function status(ProposalStatus $status): static
    {
        return $this->state(fn (): array => ['status' => $status]);
    }
}
