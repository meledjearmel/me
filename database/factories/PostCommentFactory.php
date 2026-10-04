<?php

namespace Database\Factories;

use App\Enums\CommentStatus;
use App\Models\Post;
use App\Models\PostComment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostComment>
 */
class PostCommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'author_name' => fake()->name(),
            'author_email' => fake()->safeEmail(),
            'body' => fake()->paragraph(),
            'locale' => 'fr',
            'status' => CommentStatus::Approved,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => CommentStatus::Pending]);
    }

    public function rejected(): static
    {
        return $this->state(['status' => CommentStatus::Rejected]);
    }
}
