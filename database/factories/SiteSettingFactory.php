<?php

namespace Database\Factories;

use App\Enums\CvSource;
use App\Models\SiteSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SiteSetting>
 */
class SiteSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contact_opens_drawer' => true,
            'testimonial_video_enabled' => true,
            'blog_enabled' => true,
            'cv_source' => CvSource::Uploaded,
            'congratulation_notify_minutes' => 10,
            'booking_enabled' => false,
            'booking_min_notice_hours' => 24,
            'booking_horizon_days' => 30,
            'booking_buffer_minutes' => 15,
        ];
    }

    /** Réservation ouverte, sans pause entre deux rendez-vous (créneaux réguliers pour les tests). */
    public function bookable(): static
    {
        return $this->state(fn (): array => [
            'booking_enabled' => true,
            'booking_buffer_minutes' => 0,
            'booking_video_link' => 'https://meet.example.test/armel',
        ]);
    }
}
