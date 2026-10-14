<?php

namespace Database\Factories;

use App\Enums\CommercialDocumentStatus;
use App\Enums\CommercialDocumentType;
use App\Models\CommercialDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommercialDocument>
 */
class CommercialDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => CommercialDocumentType::Quote,
            'status' => CommercialDocumentStatus::Draft,
            'locale' => 'fr',
            'client_name' => fake()->name(),
            'client_email' => fake()->safeEmail(),
            'client_company' => fake()->company(),
            'title' => 'Site vitrine',
            'currency' => 'CHF',
            'lines' => [
                ['label' => 'Site vitrine', 'description' => null, 'quantity' => 40, 'unit' => 'hour', 'unit_price' => 100],
                ['label' => 'Paiement en ligne', 'description' => null, 'quantity' => 1, 'unit' => 'flat', 'unit_price' => 1500],
            ],
            'deposit_percent' => 30,
            'valid_until' => now()->addDays(30),
        ];
    }

    public function invoice(): static
    {
        return $this->state(fn (): array => ['type' => CommercialDocumentType::Invoice, 'valid_until' => null, 'due_at' => now()->addDays(30)]);
    }
}
