<?php

namespace Database\Seeders;

use App\Models\Education;
use Illuminate\Database\Seeder;

class EducationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Le CV ne donne que l'année d'obtention : la date de début est fixée au
     * même jour que la fin, et seule l'année de fin est affichée sur le site.
     */
    public function run(): void
    {
        $educations = [
            [
                'institution' => 'École Supérieure Technique et Commerciale',
                'degree' => [
                    'fr' => 'Ingénieur de conception — Génie logiciel (Bac+5)',
                    'en' => 'Design engineer — Software engineering (Master level)',
                ],
                'field' => ['fr' => 'Génie logiciel', 'en' => 'Software engineering'],
                'start_date' => '2025-07-01',
                'end_date' => '2025-07-01',
                'sort_order' => 1,
            ],
            [
                'institution' => 'Institut de Formation Sainte Marie',
                'degree' => [
                    'fr' => "BTS Informatique — Développeur d'Application (IDA)",
                    'en' => 'Higher technician diploma in Computing — Application Developer',
                ],
                'field' => ['fr' => "Développeur d'application", 'en' => 'Application development'],
                'start_date' => '2020-07-01',
                'end_date' => '2020-07-01',
                'sort_order' => 2,
            ],
            [
                'institution' => 'Collège Ehoulé James de Divo',
                'degree' => [
                    'fr' => 'Baccalauréat — Série D',
                    'en' => 'Baccalaureate — Series D (sciences)',
                ],
                'field' => ['fr' => 'Sciences', 'en' => 'Sciences'],
                'start_date' => '2016-07-01',
                'end_date' => '2016-07-01',
                'sort_order' => 3,
            ],
        ];

        foreach ($educations as $education) {
            Education::query()->create($education);
        }
    }
}
