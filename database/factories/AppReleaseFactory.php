<?php

namespace Database\Factories;

use App\Models\AppRelease;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppRelease>
 */
class AppReleaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $buildNumber = fake()->unique()->numberBetween(1, 9999);

        return [
            'version_name' => '1.0.'.$buildNumber,
            'build_number' => $buildNumber,
            'notes' => fake()->sentence(),
        ];
    }

    /** Build dont l'APK a été envoyé, sans être publié. */
    public function uploaded(): static
    {
        return $this->state(fn (array $attributes): array => [
            'file_path' => "app-releases/app-{$attributes['version_name']}-{$attributes['build_number']}.apk",
            'size' => 1024,
            'sha256' => str_repeat('a', 64),
        ]);
    }

    public function published(): static
    {
        return $this->uploaded()->state(fn (): array => ['published_at' => now()]);
    }
}
