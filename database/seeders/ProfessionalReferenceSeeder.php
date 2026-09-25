<?php

namespace Database\Seeders;

use App\Models\ProfessionalReference;
use Illuminate\Database\Seeder;

/**
 * Mes références professionnelles, jointes au CV envoyé aux recruteurs. Elles
 * ne sont liées à aucun projet et ne s'affichent jamais sur le site public.
 */
class ProfessionalReferenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $references = [
            ['name' => 'Fourier KAMELAN', 'phone' => '+225 01 71 696 975', 'email' => 'kamfourier@gmail.com'],
            ['name' => 'Davis BAUGUINARD', 'phone' => '+225 07 58 101 470', 'email' => 'davis.bauguinard@gmail.com'],
            ['name' => 'Arouna TRAORE', 'phone' => '+225 07 49 013 922', 'email' => 'aroune75@gmail.com'],
        ];

        foreach ($references as $reference) {
            ProfessionalReference::query()->updateOrCreate(
                ['email' => $reference['email']],
                [
                    ...$reference,
                    'project_id' => null,
                    'is_public' => true,
                    'visible_fields' => ['name', 'email', 'phone'],
                ],
            );
        }
    }
}
