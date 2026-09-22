<?php

namespace Database\Seeders;

use App\Models\JobProfile;
use Illuminate\Database\Seeder;

class JobProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jobProfiles = [
            [
                'key' => 'full-stack',
                'label' => ['fr' => 'Ingénieur de conception — Génie logiciel', 'en' => 'Software Engineer'],
                'description' => [
                    'fr' => 'Conception et développement d\'applications full-stack.',
                    'en' => 'Design and development of full-stack applications.',
                ],
                'cv_description' => [
                    'fr' => 'Contenu à finaliser à partir du CV "Ingénieur de conception — Génie logiciel".',
                    'en' => 'Content to finalize from the "Software Engineer" CV.',
                ],
                'sort_order' => 1,
            ],
            [
                'key' => 'charge-it',
                'label' => ['fr' => 'Chargé IT', 'en' => 'IT Officer'],
                'description' => [
                    'fr' => 'Gestion et support des systèmes d\'information.',
                    'en' => 'Management and support of information systems.',
                ],
                'cv_description' => [
                    'fr' => 'Contenu à finaliser à partir du CV "Chargé IT".',
                    'en' => 'Content to finalize from the "IT Officer" CV.',
                ],
                'sort_order' => 2,
            ],
            [
                'key' => 'lead-tech',
                'label' => ['fr' => 'Lead Technique', 'en' => 'Tech Lead'],
                'description' => [
                    'fr' => 'Pilotage technique d\'équipes et d\'architectures.',
                    'en' => 'Technical leadership of teams and architectures.',
                ],
                'cv_description' => [
                    'fr' => 'Contenu à finaliser à partir du CV "Lead Technique".',
                    'en' => 'Content to finalize from the "Tech Lead" CV.',
                ],
                'sort_order' => 3,
            ],
            [
                'key' => 'chef-projet',
                'label' => ['fr' => 'Chef de Projet informatique', 'en' => 'IT Project Manager'],
                'description' => [
                    'fr' => 'Pilotage de projets informatiques de bout en bout.',
                    'en' => 'End-to-end management of IT projects.',
                ],
                'cv_description' => [
                    'fr' => 'Contenu à finaliser à partir du CV "Chef de Projet informatique".',
                    'en' => 'Content to finalize from the "IT Project Manager" CV.',
                ],
                'sort_order' => 4,
            ],
        ];

        foreach ($jobProfiles as $jobProfile) {
            JobProfile::query()->updateOrCreate(['key' => $jobProfile['key']], $jobProfile);
        }
    }
}
