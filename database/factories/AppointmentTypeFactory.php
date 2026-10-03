<?php

namespace Database\Factories;

use App\Enums\AppointmentLocation;
use App\Models\AppointmentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppointmentType>
 */
class AppointmentTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ['fr' => 'Appel découverte', 'en' => 'Discovery call'],
            'description' => ['fr' => 'On fait connaissance et on parle de votre projet.', 'en' => 'We get to know each other and talk about your project.'],
            'duration_minutes' => 30,
            'locations' => [AppointmentLocation::Video, AppointmentLocation::Phone],
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
