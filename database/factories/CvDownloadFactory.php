<?php

namespace Database\Factories;

use App\Enums\CvSource;
use App\Models\CvDownload;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CvDownload>
 */
class CvDownloadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'locale' => fake()->randomElement(['fr', 'en']),
            'source' => CvSource::Uploaded,
            'email' => null,
            'country_code' => 'CI',
            'country' => "Côte d'Ivoire",
            'city' => 'Abidjan',
            'referrer_host' => fake()->randomElement(['linkedin.com', 'google.com', null]),
            'device' => fake()->randomElement(['desktop', 'mobile']),
            'visitor_hash' => fake()->sha256(),
        ];
    }

    public function withEmail(): static
    {
        return $this->state(fn (): array => ['email' => fake()->safeEmail()]);
    }
}
