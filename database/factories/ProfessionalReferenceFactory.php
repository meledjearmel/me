<?php

namespace Database\Factories;

use App\Models\ProfessionalReference;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProfessionalReference>
 */
class ProfessionalReferenceFactory extends Factory
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
            'role' => fake()->jobTitle(),
            'company' => fake()->company(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'relationship' => fake()->randomElement(['Manager', 'Client', 'Collègue']),
            'project_id' => null,
            'is_public' => false,
            'visible_fields' => ['name', 'role', 'company'],
            'notes' => fake()->sentence(),
        ];
    }
}
