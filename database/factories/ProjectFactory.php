<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->word().' '.fake()->word().' '.fake()->word();

        return [
            'title' => [
                'fr' => ucfirst($title),
                'en' => ucfirst($title),
            ],
            'slug' => str($title)->slug()->toString(),
            'context' => [
                'fr' => fake()->paragraph(),
                'en' => fake()->paragraph(),
            ],
            'realization' => [
                'fr' => fake()->paragraph(),
                'en' => fake()->paragraph(),
            ],
            'result' => [
                'fr' => fake()->paragraph(),
                'en' => fake()->paragraph(),
            ],
            'repo_url' => fake()->boolean(60) ? fake()->url() : null,
            'demo_url' => fake()->boolean(40) ? fake()->url() : null,
            'is_featured' => fake()->boolean(20),
            'status' => ProjectStatus::Published,
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
