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
                'cv_last_name' => 'MELEDJE',
                'cv_first_name' => 'Gnagne Christian Armel',
                'headline' => [
                    'fr' => 'Ingénieur full-stack — dossier d\'ingénierie vivant',
                    'en' => 'Full-stack engineer — a living engineering record',
                ],
                'bio_short' => [
                    'fr' => "Ingénieur de conception en génie logiciel, j'ai plus de cinq ans d'expérience à transformer des besoins métier en solutions numériques fiables : applications web et mobiles, sécurité et gestion des accès, licences, automatisation et mise en production. Je prends en charge tout le cycle, de l'analyse au déploiement.",
                    'en' => 'Software design engineer with over five years of experience turning business needs into reliable digital solutions: web and mobile apps, security and access control, licensing, automation and production releases. I take care of the whole cycle, from analysis to deployment.',
                ],
                // Paragraphes séparés par une ligne vide : la page À propos les découpe.
                'bio_full' => [
                    'fr' => "J'interviens sur l'ensemble du cycle de vie d'un produit logiciel : compréhension du besoin, analyse, conception, développement, sécurisation, tests, déploiement et évolution. Mon approche consiste à transformer des problématiques métier en solutions fiables, maintenables et adaptées aux usages réels.

Mon expérience couvre la conception d'applications métier, de plateformes web et mobiles, de systèmes de gestion, de solutions de distribution logicielle ainsi que de composants réutilisables destinés à d'autres développeurs.

Je porte aussi une attention particulière à la sécurité, à la gestion des accès, aux licences, à l'automatisation, à la qualité logicielle et à l'exploitation, avec une approche orientée produit et mise en production.",
                    'en' => 'I work across the whole life cycle of a software product: understanding the need, analysis, design, development, security, testing, deployment and evolution. My approach is to turn business problems into reliable, maintainable solutions that fit real usage.

My experience covers business applications, web and mobile platforms, management systems, software distribution solutions and reusable components built for other developers.

I also pay particular attention to security, access management, licensing, automation, software quality and operations, with a product- and production-oriented mindset.',
                ],
                'phone' => '+225 07 87 61 46 13',
                'location' => 'Abidjan, CI',
                'social_links' => [
                    'github' => 'https://github.com/meledjearmel',
                    'linkedin' => 'https://linkedin.com/in/meledjearmel',
                ],
            ]
        );
    }
}
