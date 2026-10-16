<?php

namespace Database\Factories;

use App\Models\Distinction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Distinction>
 */
class DistinctionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = ucfirst(fake()->words(4, true));

        return [
            'title' => ['fr' => $title, 'en' => $title],
            'issuer' => fake()->company(),
            'description' => null,
            'awarded_on' => fake()->dateTimeBetween('-3 years', '-1 month')->format('Y-m-d'),
            'url' => null,
            'sort_order' => 0,
        ];
    }

    /** Rattachée à une expérience, une formation ou un projet. */
    public function forContext(Model $context): static
    {
        return $this->state(fn (): array => [
            'distinguishable_type' => $context::class,
            'distinguishable_id' => $context->getKey(),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (): array => ['status' => 'draft']);
    }
}
