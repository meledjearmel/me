<?php

namespace Database\Factories;

use App\Enums\PublicationStatus;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'title' => ['fr' => $title, 'en' => $title],
            'slug' => str($title)->slug()->toString(),
            'excerpt' => ['fr' => fake()->sentence(15), 'en' => fake()->sentence(15)],
            'body' => [
                'fr' => '<h2>Introduction</h2><p>'.implode('</p><p>', fake()->paragraphs(3)).'</p>',
                'en' => '<h2>Introduction</h2><p>'.implode('</p><p>', fake()->paragraphs(3)).'</p>',
            ],
            'is_featured' => false,
            'status' => PublicationStatus::Published,
            'published_at' => now()->subDays(fake()->numberBetween(1, 60)),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => [
            'status' => PublicationStatus::Draft,
            'published_at' => null,
        ]);
    }

    /** Publié, mais programmé dans le futur. */
    public function scheduled(): static
    {
        return $this->state(fn (): array => [
            'published_at' => now()->addWeek(),
        ]);
    }
}
