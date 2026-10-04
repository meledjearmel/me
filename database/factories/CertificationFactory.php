<?php

namespace Database\Factories;

use App\Enums\CertificationKind;
use App\Models\Certification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Certification>
 */
class CertificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = ucfirst(fake()->words(3, true));

        return [
            'kind' => CertificationKind::Certification,
            'name' => ['fr' => $name, 'en' => $name],
            'issuer' => fake()->company(),
            'issued_on' => fake()->dateTimeBetween('-3 years', '-1 month')->format('Y-m-d'),
            'expires_on' => null,
            'credential_id' => strtoupper(fake()->bothify('??-#####')),
            'credential_url' => fake()->url(),
            'sort_order' => 0,
        ];
    }

    public function course(): static
    {
        return $this->state(fn (): array => ['kind' => CertificationKind::Course, 'credential_id' => null]);
    }
}
