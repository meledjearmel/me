<?php

namespace Database\Factories;

use App\Enums\PostShareNetwork;
use App\Models\Post;
use App\Models\PostShare;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostShare>
 */
class PostShareFactory extends Factory
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
            'network' => fake()->randomElement(PostShareNetwork::cases()),
        ];
    }
}
