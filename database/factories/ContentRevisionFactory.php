<?php

namespace Database\Factories;

use App\Enums\RevisionStatus;
use App\Models\ContentRevision;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContentRevision>
 */
class ContentRevisionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'content_type' => 'project',
            'content_id' => Project::factory(),
            'changes' => ['fr' => ['tagline' => fake()->sentence()], 'en' => ['tagline' => fake()->sentence()]],
            'summary' => fake()->sentence(),
            'drafted_by' => 'Claude Code',
            'status' => RevisionStatus::Pending,
        ];
    }

    public function nowPage(): static
    {
        return $this->state(fn (): array => [
            'content_type' => 'now_page',
            'content_id' => null,
            'changes' => ['fr' => ['now_content' => fake()->paragraph()], 'en' => ['now_content' => fake()->paragraph()]],
        ]);
    }
}
