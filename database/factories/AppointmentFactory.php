<?php

namespace Database\Factories;

use App\Enums\AppointmentLocation;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AppointmentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now()->addDays(3)->setTime(10, 0);

        return [
            'appointment_type_id' => AppointmentType::factory(),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'company' => fake()->company(),
            'location' => AppointmentLocation::Video,
            'message' => fake()->sentence(),
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes(30),
            'timezone' => 'Africa/Abidjan',
            'locale' => 'fr',
            'status' => AppointmentStatus::Pending,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (): array => [
            'status' => AppointmentStatus::Confirmed,
            'confirmed_at' => now(),
            'meeting_details' => 'https://meet.example.test/abc',
        ]);
    }
}
