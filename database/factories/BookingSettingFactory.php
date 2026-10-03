<?php

namespace Database\Factories;

use App\Models\BookingSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingSetting>
 */
class BookingSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'is_enabled' => true,
            'min_notice_hours' => 24,
            'horizon_days' => 30,
            'buffer_minutes' => 0,
            'video_link' => 'https://meet.example.test/armel',
        ];
    }
}
