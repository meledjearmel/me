<?php

namespace Database\Seeders;

use App\Models\Experience;
use App\Models\ExperienceHighlight;
use Illuminate\Database\Seeder;

class ExperienceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $experiences = [
            [
                'company' => 'CIAPOL — Centre Ivoirien Antipollution',
                'role' => [
                    'fr' => "Informaticien Développeur d'Application",
                    'en' => 'Application Developer & IT Specialist',
                ],
                'location' => "Abidjan, Côte d'Ivoire",
                'start_date' => '2022-06-01',
                'end_date' => null,
                'description' => [
                    'fr' => "Intervention sur le développement et l'évolution du système d'information ainsi que sur l'exploitation de l'infrastructure informatique.",
                    'en' => 'Working on the development and evolution of the information system, and on running the IT infrastructure.',
                ],
                'sort_order' => 1,
                'highlights' => [
                    [
                        'fr' => "Travailler sur des projets internes : l'application de gestion des ICPE (installations classées pour la protection de l'environnement), une solution d'archivage numérique et un suiveur de tâches.",
                        'en' => 'Work on internal projects: the ICPE management application (classified installations for environmental protection), a digital archiving solution and a task tracker.',
                    ],
                    [
                        'fr' => 'Concevoir MyICPE, application mobile de terrain pour les agents chargés du suivi des installations classées : fiches entreprises, inspections, cartographie, fonctionnement hors connexion avec synchronisation, verrouillage par PIN et biométrie, environ 45 écrans.',
                        'en' => 'Design MyICPE, a field mobile app for the agents monitoring classified installations: company records, inspections, mapping, offline operation with synchronisation, PIN and biometric lock, around 45 screens.',
                    ],
                    [
                        'fr' => 'Intégrer à MyICPE la vérification de licence et les mises à jour, et réaliser son site vitrine.',
                        'en' => 'Add licence checks and updates to MyICPE, and build its showcase website.',
                    ],
                    [
                        'fr' => 'Concevoir Pollumar (en cours de développement), plateforme de suivi de formation avec assistant conversationnel et suivi de la consommation.',
                        'en' => 'Design Pollumar (in development), a training-tracking platform with a conversational assistant and usage tracking.',
                    ],
                    [
                        'fr' => "Encadrer environ neuf collaborateurs en début de parcours : répartition des tâches, revues de code, formation, suivi d'avancement et évaluation de leur travail.",
                        'en' => 'Guide about nine team members early in their careers: task assignment, code reviews, training, progress follow-up and assessment of their work.',
                    ],
                    [
                        'fr' => "Gérer le site officiel de l'organisme.",
                        'en' => "Run the organisation's official website.",
                    ],
                    [
                        'fr' => 'Configurer et maintenir les serveurs Linux (Proxmox) qui hébergent les services internes pour environ 200 utilisateurs, avec une supervision par Zabbix.',
                        'en' => 'Configure and maintain the Linux servers (Proxmox) hosting the internal services for about 200 users, monitored with Zabbix.',
                    ],
                    [
                        'fr' => 'Assurer le support technique et traiter les incidents applicatifs signalés par les utilisateurs.',
                        'en' => 'Provide technical support and handle the application incidents reported by users.',
                    ],
                    [
                        'fr' => "Gérer les comptes, les accès et les droits d'administration attribués aux personnes habilitées.",
                        'en' => 'Manage the accounts, access and administration rights granted to authorised people.',
                    ],
                    [
                        'fr' => "Déployer et administrer GLPI pour le suivi des équipements, des licences et des demandes d'intervention.",
                        'en' => 'Deploy and run GLPI to track equipment, licences and intervention requests.',
                    ],
                    [
                        'fr' => 'Participer à la mise en œuvre et à la restauration de sauvegardes et de snapshots réguliers, en local et sur un serveur distant, et planifier les mises à jour des systèmes et des applications.',
                        'en' => 'Take part in setting up and restoring regular backups and snapshots, locally and on a remote server, and schedule system and application updates.',
                    ],
                    [
                        'fr' => 'Participer à des tournées de formation des agents aux bonnes pratiques informatiques et préparer les rapports de suivi des activités IT.',
                        'en' => 'Take part in training rounds teaching staff good IT practices, and prepare IT activity reports.',
                    ],
                ],
            ],
            [
                'company' => 'GetAll',
                'role' => [
                    'fr' => 'Développeur web et intégrateur freelance',
                    'en' => 'Freelance web developer and integrator',
                ],
                'location' => 'CH · Remote (Abidjan)',
                'start_date' => '2024-09-01',
                'end_date' => '2025-07-31',
                'description' => [
                    'fr' => 'Nouveau nom de Digitalize Me : même équipe, mêmes clients, et une collaboration qui se poursuit sur les mêmes activités.',
                    'en' => 'The new name of Digitalize Me: same team, same clients, and a collaboration that carries on with the same activities.',
                ],
                'sort_order' => 2,
                'highlights' => [
                    [
                        'fr' => "Poursuivre la réalisation de sites web sur mesure, plateformes e-commerce et sites vitrines. Sur l'ensemble de la période freelance : environ 11 sites, dont 8 plateformes e-commerce et 3 sites vitrines.",
                        'en' => 'Keep building tailor-made websites, e-commerce platforms and showcase sites. Across the whole freelance period: about 11 sites, including 8 e-commerce platforms and 3 showcase sites.',
                    ],
                    [
                        'fr' => "Encadrer environ trois collaborateurs sur l'ensemble de la période freelance : répartition des tâches, revues de code, formation, suivi d'avancement et évaluation.",
                        'en' => 'Guide about three team members across the whole freelance period: task assignment, code reviews, training, progress follow-up and assessment.',
                    ],
                    [
                        'fr' => "Travailler sur une plateforme de réservation en ligne de studios d'enregistrement de podcasts : choix du lieu, de l'ambiance, de la date et de la durée, avec tarifs en francs suisses.",
                        'en' => 'Work on an online booking platform for podcast recording studios: choice of location, ambiance, date and duration, with prices in Swiss francs.',
                    ],
                    [
                        'fr' => "Réaliser le site vitrine d'une entreprise de transport de voyageurs en autocar : services, flotte, transport scolaire et touristique.",
                        'en' => 'Build the showcase website of a coach-transport company: services, fleet, school and tourist transport.',
                    ],
                    [
                        'fr' => "Poursuivre l'évolution et la maintenance de la boutique en ligne du média d'art et de design, démarrée chez Digitalize Me.",
                        'en' => 'Keep evolving and maintaining the online shop of the art and design media outlet, started at Digitalize Me.',
                    ],
                ],
            ],
            [
                'company' => 'Digitalize Me',
                'role' => [
                    'fr' => 'Développeur web et intégrateur freelance',
                    'en' => 'Freelance web developer and integrator',
                ],
                'location' => 'CH · Remote (Abidjan)',
                'start_date' => '2022-02-01',
                'end_date' => '2024-09-01',
                'description' => [
                    'fr' => 'Nouveau nom de Webleman à partir de février 2022, avec les mêmes personnes : je poursuis les mêmes activités auprès des mêmes clients. La structure devient ensuite GetAll.',
                    'en' => 'The new name of Webleman from February 2022, with the same people: I carry on the same activities for the same clients. The company later became GetAll.',
                ],
                'sort_order' => 3,
                'highlights' => [
                    [
                        'fr' => "Accompagner des projets numériques, de la réalisation de sites web à la conception d'applications répondant à des besoins métier spécifiques.",
                        'en' => 'Support digital projects, from building websites to designing applications for specific business needs.',
                    ],
                    [
                        'fr' => "Assurer la maintenance, les mises à jour et la sécurité des sites livrés, notamment sous WordPress, et gérer l'hébergement et les serveurs associés.",
                        'en' => 'Handle maintenance, updates and security of the delivered sites, notably on WordPress, and manage the associated hosting and servers.',
                    ],
                    [
                        'fr' => "Réaliser huit boutiques en ligne pour des clients, presque toutes en intégration WordPress (l'une d'elles en ReactJS avec intégration WordPress), dont une boutique d'accessoires de protection pour appareils Apple, avec livraison express et livraison offerte en Suisse.",
                        'en' => 'Build eight online shops for clients, almost all as WordPress integrations (one of them in ReactJS with WordPress integration), including a shop for protective accessories for Apple devices, with express delivery and free shipping in Switzerland.',
                    ],
                    [
                        'fr' => 'Réaliser deux sites vitrines en intégration WordPress.',
                        'en' => 'Build two showcase websites as WordPress integrations.',
                    ],
                    [
                        'fr' => 'Concevoir une application web de gestion de fiches produits pour WordPress, avec Laravel et Vue.',
                        'en' => 'Design a web application to manage product sheets for WordPress, built with Laravel and Vue.',
                    ],
                    [
                        'fr' => "Réaliser, parmi d'autres, une boutique en ligne pour un média d'art et de design (magazines et objets de collection), le site d'une entreprise de pose de cuisines et d'appareils multimédias, et des outils pour un opérateur de boutiques en ligne : gestion des stocks, des commandes et des expéditions sur plusieurs canaux de vente.",
                        'en' => 'Build, among others, an online shop for an art and design media outlet (magazines and collectibles), the website of a kitchen and multimedia installation company, and tools for an online-store operator: managing stock, orders and shipping across several sales channels.',
                    ],
                    [
                        'fr' => "Intervenir sur une plateforme d'analyse publicitaire pour e-commerçants, qui suit les publicités diffusées sur les réseaux sociaux pour repérer les produits qui se vendent.",
                        'en' => 'Work on an ad-analysis platform for e-merchants, which tracks ads on social networks to spot products that sell well.',
                    ],
                ],
            ],
            [
                'company' => 'Webleman',
                'role' => [
                    'fr' => 'Développeur web et intégrateur freelance',
                    'en' => 'Freelance web developer and integrator',
                ],
                'location' => 'CH · Remote (Abidjan)',
                'start_date' => '2020-10-01',
                'end_date' => '2022-02-01',
                'description' => [
                    'fr' => 'Mes débuts en freelance : réalisation de sites web pour des clients. La structure est devenue Digitalize Me en février 2022.',
                    'en' => 'My freelance beginnings: building websites for clients. The company became Digitalize Me in February 2022.',
                ],
                'sort_order' => 4,
                'highlights' => [
                    [
                        'fr' => 'Concevoir et développer des sites web pour des clients, de la maquette à la mise en ligne.',
                        'en' => 'Design and build websites for clients, from mockup to launch.',
                    ],
                    [
                        'fr' => "Réaliser le site d'un cabinet de gynécologie en Suisse romande : présentation du médecin et de ses consultations, et prise de rendez-vous pour les patientes.",
                        'en' => 'Build the website of a gynaecology practice in French-speaking Switzerland: presentation of the doctor and consultations, and appointment booking for patients.',
                    ],
                    [
                        'fr' => "Intégrer des interfaces et suivre les demandes d'évolution des clients.",
                        'en' => "Integrate interfaces and follow up on clients' change requests.",
                    ],
                ],
            ],
        ];

        foreach ($experiences as $data) {
            $highlights = $data['highlights'];
            unset($data['highlights']);

            $experience = Experience::query()->create($data);

            foreach ($highlights as $index => $text) {
                ExperienceHighlight::query()->create([
                    'experience_id' => $experience->id,
                    'text' => $text,
                    'sort_order' => $index + 1,
                ]);
            }
        }
    }
}
