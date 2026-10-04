<?php

namespace Database\Factories;

use App\Enums\PostReactionType;
use App\Models\Post;
use App\Models\PostReaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostReaction>
 */
class PostReactionFactory extends Factory
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
            'type' => fake()->randomElement(PostReactionType::cases()),
            'reader_hash' => hash('sha256', fake()->uuid()),
        ];
    }
}
