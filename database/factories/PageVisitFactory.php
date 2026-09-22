<?php

namespace Database\Factories;

use App\Models\PageVisit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PageVisit>
 */
class PageVisitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'visitable_type' => null,
            'visitable_id' => null,
            'path' => '/'.fake()->slug(2),
            'referrer' => fake()->boolean(50) ? fake()->url() : null,
            'device' => fake()->randomElement(['desktop', 'mobile', 'tablet']),
            'duration_seconds' => fake()->numberBetween(5, 600),
        ];
    }
}
