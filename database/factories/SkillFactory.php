<?php

namespace Database\Factories;

use App\Models\Domain;
use App\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Skill>
 */
class SkillFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->word().' '.fake()->word();

        return [
            'domain_id' => Domain::factory(),
            'name' => [
                'fr' => ucfirst($name),
                'en' => ucfirst($name),
            ],
            'description' => [
                'fr' => fake()->sentence(),
                'en' => fake()->sentence(),
            ],
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
