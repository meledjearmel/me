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
            'email' => 'test@example.com',
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
            TestimonialSeeder::class,
            ProfessionalReferenceSeeder::class,
        ]);
    }
}
