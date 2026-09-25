<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Armel Meledje',
            'email' => 'me@armeldev.xyz',
        ]);

        $this->call([
            DomainSeeder::class,
            TechnologySeeder::class,
            JobProfileSeeder::class,
            ProfileSeeder::class,
            SkillSeeder::class,
            EducationSeeder::class,
            ExperienceSeeder::class,
            ProjectSeeder::class,
            ProfessionalReferenceSeeder::class,
        ]);

        // Témoignages fictifs : uniquement pour visualiser le site en local.
        if (! app()->isProduction()) {
            $this->call(TestimonialSeeder::class);
        }
    }
}
