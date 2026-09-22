<?php

namespace Database\Seeders;

use App\Models\Profile;
use Illuminate\Database\Seeder;

class ProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Profile::query()->updateOrCreate(
            ['email' => 'meledjearmel@gmail.com'],
            [
                'name' => 'Armel Meledje',
                'headline' => [
                    'fr' => 'Ingénieur full-stack — dossier d\'ingénierie vivant',
                    'en' => 'Full-stack engineer — a living engineering record',
                ],
                'bio_short' => [
                    'fr' => 'Contenu à finaliser à partir du brief créatif.',
                    'en' => 'Content to finalize from the creative brief.',
                ],
                'bio_full' => [
                    'fr' => 'Contenu à finaliser à partir du brief créatif.',
                    'en' => 'Content to finalize from the creative brief.',
                ],
                'phone' => null,
                'location' => null,
                'social_links' => [
                    'github' => 'https://github.com/',
                    'linkedin' => 'https://linkedin.com/in/',
                ],
            ]
        );
    }
}
