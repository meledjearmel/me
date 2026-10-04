<?php

namespace Database\Factories;

use App\Enums\UsesCategory;
use App\Models\UsesItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UsesItem>
 */
class UsesItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $description = fake()->sentence();

        return [
            'category' => fake()->randomElement(UsesCategory::cases()),
            'name' => ucfirst(fake()->unique()->word()),
            'description' => ['fr' => $description, 'en' => $description],
            'url' => fake()->url(),
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
