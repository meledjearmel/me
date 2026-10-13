<?php

namespace Database\Factories;

use App\Enums\PublicationStatus;
use App\Enums\ServicePriceMode;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(2),
            'title' => ['fr' => 'Site vitrine', 'en' => 'Showcase website'],
            'tagline' => ['fr' => 'Présenter votre activité, clairement.', 'en' => 'Present your business, clearly.'],
            'description' => ['fr' => 'Un site rapide et soigné pour être trouvé et contacté.', 'en' => 'A fast, polished website to be found and contacted.'],
            'includes' => ['fr' => "Design sur mesure\nReferencement de base\nMise en ligne", 'en' => "Custom design\nBasic SEO\nGo-live"],
            'price_mode' => ServicePriceMode::Quote,
            'status' => PublicationStatus::Published,
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => ['status' => PublicationStatus::Draft]);
    }

    /** @param  array<string, int>  $prices */
    public function manual(array $prices = ['CHF' => 2500, 'EUR' => 2400]): static
    {
        return $this->state(fn (): array => ['price_mode' => ServicePriceMode::Manual, 'manual_prices' => $prices]);
    }
}
