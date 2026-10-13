<?php

namespace Database\Factories;

use App\Enums\EstimatorBilling;
use App\Enums\EstimatorItemKind;
use App\Models\EstimatorItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstimatorItem>
 */
class EstimatorItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kind' => EstimatorItemKind::ProjectType,
            'name' => ['fr' => 'Site vitrine', 'en' => 'Showcase website'],
            'description' => ['fr' => 'Quelques pages pour présenter votre activité.', 'en' => 'A few pages to present your business.'],
            'hours_min' => 30,
            'hours_max' => 50,
            'billing' => EstimatorBilling::Fixed,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }

    public function hourly(): static
    {
        return $this->state(fn (): array => [
            'name' => ['fr' => 'Maintenance et évolutions', 'en' => 'Maintenance and improvements'],
            'billing' => EstimatorBilling::Hourly,
        ]);
    }

    public function feature(): static
    {
        return $this->state(fn (): array => [
            'kind' => EstimatorItemKind::Feature,
            'name' => ['fr' => 'Paiement en ligne', 'en' => 'Online payment'],
            'description' => ['fr' => null, 'en' => null],
            'hours_min' => 10,
            'hours_max' => 20,
            'billing' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
