<?php

namespace Database\Factories;

use App\Enums\JobApplicationStatus;
use App\Models\JobApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobApplication>
 */
class JobApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company' => 'Acme SA',
            'contact_name' => 'Claire Martin',
            'position' => 'Développeur full-stack',
            'locale' => 'fr',
            'intro' => "Bonjour,\n\nVotre offre m'a parlé tout de suite : voici les projets qui y répondent le mieux.",
            'status' => JobApplicationStatus::Active,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => ['status' => JobApplicationStatus::Draft]);
    }

    public function archived(): static
    {
        return $this->state(fn (): array => ['status' => JobApplicationStatus::Archived]);
    }
}
