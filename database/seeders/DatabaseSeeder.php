<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->seedAdministrator();

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

        // Données générées (avis fictifs) :
        // uniquement pour travailler en local, jamais en production.
        if (app()->environment('local')) {
            $this->call(TestimonialSeeder::class);
        }
    }

    /**
     * Crée mon compte s'il n'existe pas encore (jamais écrasé au re-seed).
     * Mot de passe initial : `ADMIN_PASSWORD`, à changer après la première connexion ;
     * à défaut, un mot de passe aléatoire est généré et affiché une seule fois.
     */
    private function seedAdministrator(): void
    {
        $email = 'me@armeldev.xyz';

        if (User::query()->where('email', $email)->exists()) {
            return;
        }

        $password = config('app.admin_password') ?: Str::password(20);

        User::query()->create([
            'name' => 'Armel Meledje',
            'email' => $email,
            'password' => $password,
            'email_verified_at' => now(),
        ]);

        if (! config('app.admin_password')) {
            $this->command?->warn("Compte {$email} créé, mot de passe : {$password}");
        }
    }
}
