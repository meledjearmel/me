<?php

namespace Database\Factories;

use App\Models\McpActivity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<McpActivity>
 */
class McpActivityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client' => 'Claude Code',
            'kind' => 'tool',
            'name' => 'get-catalog',
            'target_type' => null,
            'target_id' => null,
            'success' => true,
            'error' => null,
            'duration_ms' => fake()->numberBetween(20, 900),
        ];
    }

    public function failed(string $error = 'Contenu introuvable.'): static
    {
        return $this->state(fn (): array => ['success' => false, 'error' => $error]);
    }
}
